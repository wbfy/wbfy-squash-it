<?php
class wbfy_si_Libs_Html_Inputs
{
    /**
     * Generate select based on numeric range
     *
     * @param int $min      starting value
     * @param int $max      last value
     * @param array $attrs  html attributes eg: $attrs['value'] = 1
     *                      selected option is set to 'value' attribute
     * @param int $inc      increment between options in range, default is 1
     *
     * @return string       the generated html
     */
    public static function selectRange($min, $max, $attrs = array(), $inc = 1)
    {
        $value = $min;
        if (isset($attrs['value'])) {
            $value = $attrs['value'];
            unset($attrs['value']);
        }

        $html = '<select';
        $html .= self::attrs($attrs);
        $html .= '>';

        $i = $min;
        while ($i <= $max) {
            $selected = ($i == $value) ? ' selected' : '';
            $html .= '<option value="' . $i . '"' . $selected . '>' . (string) $i . '</option>';
            $i = $i + $inc;
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * Generate select from array
     *
     * @param array $list   options in list eg: $list[value] = name
     * @param array $attrs  html attributes eg: $attrs['value'] = 'foo'
     *                      selected option is set to 'value' attribute
     *
     * @return string       the generated html
     */
    public static function selectList($list, $attrs = array())
    {
        $value = array_key_first($list);
        if (isset($attrs['value'])) {
            $value = $attrs['value'];
            unset($attrs['value']);
        }

        $html = '<select';
        $html .= self::attrs($attrs);
        $html .= '>';
        foreach ($list as $id => $name) {
            $selected = ($id == $value) ? ' selected' : '';
            $html .= '<option value="' . $id . '"' . $selected . '>' . (string) $name . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * Generate text input
     *
     * @param array $attrs  html attributes eg: $attrs['maxlength'] = 10
     *
     * @return string       the generated html
     */
    public static function inputText($attrs = array())
    {
        return '<input type="text"' . self::attrs($attrs) . '>';
    }

    /**
     * Generate checkbox input
     *
     * @param array $attrs  html attributes eg: $attrs['class'] = 'foo'
     *
     * @return string       the generated html
     */
    public static function inputCheck($attrs = array())
    {
        if (isset($attrs['value']) && $attrs['value']) {
            $attrs['checked'] = 'on';
        }
        unset($attrs['value']);

        $pre  = '';
        $post = '';
        if (isset($attrs['label'])) {
            $pre  = '<label>';
            $post = esc_html__($attrs['label']) . '</label>';
            unset($attrs['label']);
        }

        return $pre . '<input type="checkbox"' . self::attrs($attrs) . '>' . $post;
    }

    /**
     * Generate html attributes from attribute list
     *
     * @param $attrs    List of attributes eg: $attrs['maxlength'] = 10
     *
     * @return string   the generated html
     */
    private static function attrs($attrs)
    {
        $html = '';
        if (is_array($attrs)) {
            foreach ($attrs as $attr => $value) {
                $html .= ' ' . $attr . '="' . $value . '"';
            }
        }
        return $html;
    }
}
