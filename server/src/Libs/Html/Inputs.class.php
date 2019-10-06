<?php
class wbfy_si_Libs_Html_Inputs
{
    public static function selectRange($min, $max, $attrs = array(), $inc = 1)
    {
        $html = '<select';
        $html .= self::attrs($attrs);
        $html .= '>';

        $value = $min;
        if (isset($attrs['value'])) {
            $value = $attrs['value'];
            unset($attrs['value']);
        }

        $i = $min;
        while ($i <= $max) {
            $selected = ($i == $value) ? ' selected' : '';
            $html .= '<option value="' . $i . '"' . $selected . '>' . (string) $i . '</option>';
            $i = $i + $inc;
        }
        $html .= '</select>';
        return $html;
    }

    public static function inputText($attrs = array())
    {
        return '<input type="text"' . self::attrs($attrs) . '>';
    }

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
