/**
 * Javascript AJAX client for Squash It! Now
 */
(
	function ($) {
		var MAX_RETRIES = 3;

		/**
		 * Initialise after document loaded
		 */
		$(document).ready(
			function () {
				$('.wbfy-squash-it-now #submit').click(squashItNow);
				if (window.MutationObserver) {
					/**
					 * Create an observer to monitor the log <div> and scroll it up when it
					 * has new children (ie: log entries) that will change the height.
					 */
					var log = document.querySelector('.wbfy-squash-it-now #wbfy-si-log'),
						observer = new MutationObserver
							(
								function () {
									log.scrollTop = log.scrollHeight;
								}
							);
					observer.observe(log, { childList: true });
				}
			}
		);

		/**
		 * Process and resize images
		 */
		function squashItNow(event) {
			var $log = $('.wbfy-squash-it-now #wbfy-si-log'),
				dryRun = ($('.wbfy-squash-it-now #wbfy-si-dry_run').is(':checked')) ? 'yes' : 'no';

			event.preventDefault();

			squashItStarted();
			squashItLog($log, 'Querying server for file list');
			squashItLog($log, 'This can take a few minutes if you have uploaded a lot of images so please be patient');
			squashItLog($log, 'If you navigate away during this process Squash It! won&#39;t update any images');
			if (dryRun == 'yes') {
				squashItLog($log, '** Dry run mode is enabled so no files will be modified **', 'info');
			}

			// Get filelist
			$.get(
				{
					url: ajaxurl,
					data: {
						action: 'wbfy_si_files',
						verify: $('#wbfy-si-verify').val()
					},
					cache: false,
					timeout: 0
				}
			)
				.done(
					function (response) {
						var data = response.data;

						if (data.status !== 'OK') {
							// Log error and finish
							squashItLog($log, data.status, 'error');
							squashItFinished();
							return;
						}

						var displayCount = parseInt(data.files.count).toLocaleString('en'),
							displaySecs = parseFloat(data.secs).toLocaleString('en'),
							dirs = Object.keys(data.files.list);

						if (data.files.count > 0) {
							if (data.batch_mode.on) {
								squashItLog($log, '** Using batch mode for a maximum of ' + data.batch_mode.limit + ' files per run **', 'info');
								squashItLog($log, '** Enable setting of PHP_OPTION max_execution_time on the server to prevent batch mode **', 'info');
							}
							squashItLog($log, 'Found ' + displayCount + ' files in ' + dirs.length + ' folders in ' + displaySecs + ' seconds');

							// Start recursion and process files one at a time
							squashItProcessFile(dirs, data.files, $log, dryRun);
						} else {
							squashItLog($log, 'No files found', 'error');
						}
					}
				)
				.fail(
					function (err, textStatus, errorThrown) {
						// Log error and finish
						squashItLog($log, 'There was a problem fetching the filelist. The server said "' + textStatus + ', ' + errorThrown + '". Please try again later.', 'error');
						squashItFinished();
					}
				);
		}

		/**
		 * Recursively resize images one by one via AJAX
		 * With many files doing this concurrently (with $.each for example) can
		 * put very high load on the server and lead to many failures
		 */
		function squashItProcessFile(dirs, files, $log, dryRun) {
			var me = squashItProcessFile;
			/**
			 * Only set on first time the function is called,
			 * not on any of the following recursions
			 */
			if (typeof $log !== 'undefined') {
				me.total = files.count;
				me.processed = 1;
				me.success = 0;

				me.dir = 0;
				me.file = 0;

				me.started = Date.now();
				me.dryRun = dryRun;
				me.$log = $log;
				me.retryCount = MAX_RETRIES;

				me.sizes = {
					original: 0,
					squashed: 0
				};
			}

			// Display folder details if first pass of first file in folder
			if (me.dir >= dirs.length && me.file == 0 && me.retryCount == MAX_RETRIES) {
				if (me.dryRun == 'yes') {
					squashItLog(me.$log, 'Dry running Squash It! scanned a total of ' + me.total + ' images in ' + squashItDuration(me.started) + ' seconds', 'success');
					if (me.success != me.total) {
						squashItLog(me.$log, me.success + ' images could be squashed', 'success');
					}
					squashItLog(me.$log, 'Current total space used for images: ' + squashItDisplayBytesAsMB(me.sizes.original), 'success indent');
					squashItLog(me.$log, 'Projected space used after Squashing It!: ' + squashItDisplayBytesAsMB(me.sizes.squashed), 'success indent');
					squashItLog(me.$log, 'Projected space saved: ' + squashItDisplayBytesAsMB(me.sizes.original - me.sizes.squashed), 'success indent');
					if (me.success != me.total) {
						squashItLog(
							me.$log,
							(me.total - me.success) + ' images would be left unsquashed - see log details for more information',
							'success indent'
						);
					}
					squashItLog(me.$log, '** No files were modified as dry run mode was enabled **', 'info');
				} else {
					squashItLog(me.$log, 'Squash It! processed a total of ' + me.total + ' images in ' + squashItDuration(me.started) + ' seconds', 'success');
					if (me.success != me.total) {
						squashItLog(me.$log, me.success + ' images were squashed', 'success');
					}
					squashItLog(me.$log, 'Original total space used for images: ' + squashItDisplayBytesAsMB(me.sizes.original), 'success indent');
					squashItLog(me.$log, 'Space used after Squashing It!: ' + squashItDisplayBytesAsMB(me.sizes.squashed), 'success indent');
					squashItLog(me.$log, 'Space saved: ' + squashItDisplayBytesAsMB(me.sizes.original - me.sizes.squashed), 'success indent');
					if (me.success != me.total) {
						squashItLog(
							me.$log,
							(me.total - me.success) + ' images were left unsquashed - see log details for more information',
							'success indent'
						);
					}
				}
				squashItFinished();
				return;
			}

			if (me.file == 0 && me.retryCount == MAX_RETRIES) {
				squashItLog(me.$log, 'Processing ' + files.list[dirs[me.dir]].length + ' files in ' + dirs[me.dir]);
			}

			var fileinfo = files.list[dirs[me.dir]][me.file];

			// Log image details
			if (me.retryCount == MAX_RETRIES) {
				squashItLog
					(
						me.$log,
						me.processed + '/' + me.total +
						' -> ' + fileinfo.name +
						' [' + fileinfo.size.width + ' x ' + fileinfo.size.height +
						' ' + squashItDisplayBytesAsMB(fileinfo.size.bytes) + '] #' + fileinfo.id
					);
			}

			// Resize image
			$.get(
				{
					url: ajaxurl,
					data:
					{
						action: 'wbfy_si_file',
						verify: $('#wbfy-si-verify').val(),
						path: dirs[me.dir],
						filename: fileinfo.name,
						id: fileinfo.id,
						dry_run: me.dryRun
					},
					cache: false,
					timeout: 0
				}
			)
				.done(
					function (response) {
						var data = response.data;

						// Update recursion counters
						me.file++;
						me.processed++;
						me.success++;
						if (me.file >= files.list[dirs[me.dir]].length) {
							me.file = 0;
							me.dir++;
						}

						// Reset retry count in case of any retries previously
						squashItProcessFile.retryCount = 3;

						if (data.status == 'OK') {
							me.sizes.original += data.sizes.original;
							me.sizes.squashed += data.sizes.squashed;
						} else {
							squashItLog(me.$log, data.status, 'error');
						}

						// Next image is only processed when the previous one is completed (Ajax call is done)
						squashItProcessFile(dirs, files);
					}
				)
				.fail(
					function (err, txtStatus) {
						if (me.retryCount > 0) {
							// Retry processing file
							me.retryCount--;
							squashItProcessFile(dirs, files);
						} else {
							// Fail and move on to next file after MAX_RETRIES retries
							me.file++;
							if (me.file >= files.list[dirs[me.dir]].length) {
								me.file = 0;
								me.dir++;
							}
							me.retryCount = MAX_RETRIES;
							me.processed++;

							squashItLog(me.$log, 'Request failed after ' + MAX_RETRIES + ' retries. The file could not be Squashed.', 'error indent');
							squashItProcessFile(dirs, files);
						}
					}
				);
		}

		/**
		 * Calculate duration between date / time now and date / time started
		 *
		 * @param object Start datetime Date.now()
		 */
		function squashItDuration(started) {
			return parseFloat((Date.now() - started) / 1000).toLocaleString('en');
		}

		/**
		 * Append log line into log <div> on SquashIt Now! page
		 *
		 * @param objact $log    jQuery log reference
		 * @param string message Text message to output
		 * @param string level   Log level and display options to be applied
		 *                       eg: error, info, indented
		 *                       Can be multiple options separated by spaces
		 *                       Prefixed with 'wbfy-si' to create class name
		 */
		function squashItLog($log, message, level) {
			var entry = '';
			if (typeof level !== 'undefined') {
				entry = $('<div>', { class: (' ' + level).replace(new RegExp(' ', 'g'), ' wbfy-si-').trim() });
			} else {
				entry = $('<div>');
			}
			$log.append(entry.html(message));
		}

		/**
		 * Called when Squash It! Now is run
		 * Disable dry run checkbox and Squash It! Now submit button
		 */
		function squashItStarted() {
			// Clear and show log
			$('.wbfy-squash-it-now #wbfy-si-log').show().html('');
			$('.wbfy-squash-it-now #wbfy-si-dry_run, .wbfy-squash-it-now #submit').prop('disabled', true);
		}

		/**
		 * Called when Squash It! Now is finished
		 * Re-enabled dry run checkbox and Squash It! Now submit button
		 */
		function squashItFinished() {
			$('.wbfy-squash-it-now #wbfy-si-dry_run, .wbfy-squash-it-now #submit').prop('disabled', false);
		}

		/**
		 * Convert bytes into MB and format for display
		 *
		 * @return string Formatted string for display
		 */
		function squashItDisplayBytesAsMB(bytes) {
			return ((parseInt(bytes) / 1024) / 1000).toLocaleString('en') + 'MB';
		}
	}
)(jQuery);
