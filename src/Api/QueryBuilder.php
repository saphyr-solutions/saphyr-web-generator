<?php

namespace SaphyrWebGenerator\Api;

class QueryBuilder
{

    protected $module;

    protected $query = [];

    protected $fields;

    protected $limit = null;

    protected $order = null;


    protected $results;


    function __construct()
    {
    }


    function setModule(int $mid)
    {
        $this->module = $mid;
    }


    function setFields(array $fields)
    {
        $this->fields = $fields["results"] ?? $fields;
    }


    function addFilter(array $filter)
    {
        $this->query = $filter;
    }


    function setLimit(int $offset, int $length)
    {
        $this->limit = [$offset, $length];
    }


    function addOrder(string $sortfield, string $sortmode)
    {
        if (!is_array($this->order)) {
            $this->order = [];
        }
        $this->order[] = ['key' => $sortfield, 'mode' => strtolower($sortmode)];
    }


    /**
     * @return mixed
     */
    function getModule()
    {
        return $this->module;
    }


    /**
     * Matching entre élément dans tableau et "pseudo requête de recherche"
     * A améliorer et rendre "plus pratique"
     * @param array $item
     * @return bool
     */
    function match(array $item): bool
    {
        $found = true;
        if (is_array($this->query)) {
            foreach ($this->query as $querie) {
                $field = array_filter($this->fields, function ($e) use ($querie) {
                    if (isset($querie['id'])) {
                        return $e['id'] == $querie['id'];
                    } else if (isset($querie['reference'])) {
                        return $e['config']['reference'] == $querie['reference'];
                    } else if (isset($querie['label'])) {
                        return $e['config']['label'] == $querie['label'];
                    }
                    return false;
                });
                if ($field) {
                    $fieldFound = false;
                    $field = array_shift($field);
                    $field_reference = $field['config']['reference'];
                    $cond = isset($querie['condition']) ? $querie['condition'] : '=';
                    switch ($cond) {
                        case '%' :
                            $fieldFound = mb_eregi($querie['value'], $item[$field_reference]);
                            break;
                        case '=' :
                            $fieldFound = $item[$field_reference] == $querie['value'];
                            break;
                        case '!=':
                            $fieldFound = $item[$field_reference] != $querie['value'];
                            break;
                        case '>':
                        case '>=':
                        case '<':
                        case '<=':
                            switch ($field['type']) {
                                case 'DateTime':
                                    $str = '$fieldFound=strtotime($item[$field_reference])' . $cond . 'strtotime($querie[\'value\']);';
                                    eval($str);
                                    break;
                            }
                            break;
                    }
                    if (!$fieldFound) {
                        $found = false;
                    }
                }
            }
            return $found;
        }
        return false;
    }
    
    /**
     *
     */
    protected function parseOrder()
    {
        if ($this->order) {
            foreach ($this->order as $orderBy) {
                $keys = [];
                $field = $orderBy['key'];
                $field_infos = array_filter($this->fields, function ($e) use ($field) {
                    return ($e['id'] == $field || (isset($e['config']['label']) && ($e['config']['label'] == $field)) || (isset($e['config']['reference']) && ($e['config']['reference'] == $field)));
                });

                if ($field_infos) {
                    $field_infos = array_shift($field_infos);
                    $sort = $orderBy['mode'];
                    foreach ($this->results as $key => $values) {
                        $value = $values["values"][$field]["value"];
                        switch ($field_infos['type']) {
                            case 'DateTime':
                                $value = strtotime($value);
                                break;
                            default:
                                $value = trim(strtolower($this->unaccent($value)));
                                break;
                        }

                        $keys[$key] = $value;
                    }

                    $sort = ($sort == 'asc') ? SORT_ASC : SORT_DESC;
                    array_multisort($keys, $sort, $this->results);
                }
            }
        }
    }
    
    /**
     *
     */
    protected function parseLimits()
    {
        if (is_array($this->limit) && count($this->limit) == 2) {
            $this->results = array_slice($this->results, $this->limit[0], $this->limit[1]);
        }
    }


    /**
     * @param array $items
     */
    protected function parseFilters(array $items)
    {
        $this->results = array_filter($items, function ($item) {
            return $this->match($item);
        });
    }


    /**
     * @param array $items
     * @return array
     */

    function getResults(array $items): array
    {
        $this->parseFilters($items);
        $this->parseOrder();
        $this->parseLimits();
        return $this->results;
    }


    /**
     * @param string $string
     * @return string
     */
    protected function unaccent(string $string): string
    {
        if (preg_match('/[\x80-\xff]/', $string) === false) {
            return $string;
        }

        $ACCENTED_CHARACTERS = ['À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Æ' => 'Ae', 'Å' => 'Aa', 'æ' => 'a', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ý' => 'Y', 'ß' => 'ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'aa', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ý' => 'y', 'ÿ' => 'y', 'Ā' => 'A', 'ā' => 'a', 'Ă' => 'A', 'ă' => 'a', 'Ą' => 'A', 'ą' => 'a', 'Ć' => 'C', 'ć' => 'c', 'Ĉ' => 'C', 'ĉ' => 'c', 'Ċ' => 'C', 'ċ' => 'c', 'Č' => 'C', 'č' => 'c', 'Ď' => 'D', 'ď' => 'd', 'Đ' => 'D', 'đ' => 'd', 'Ē' => 'E', 'ē' => 'e', 'Ĕ' => 'E', 'ĕ' => 'e', 'Ė' => 'E', 'ė' => 'e', 'Ę' => 'E', 'ę' => 'e', 'Ě' => 'E', 'ě' => 'e', 'Ĝ' => 'G', 'ĝ' => 'g', 'Ğ' => 'G', 'ğ' => 'g', 'Ġ' => 'G', 'ġ' => 'g', 'Ģ' => 'G', 'ģ' => 'g', 'Ĥ' => 'H', 'ĥ' => 'h', 'Ħ' => 'H', 'ħ' => 'h', 'Ĩ' => 'I', 'ĩ' => 'i', 'Ī' => 'I', 'ī' => 'i', 'Ĭ' => 'I', 'ĭ' => 'i', 'Į' => 'I', 'į' => 'i', 'İ' => 'I', 'ı' => 'i', 'Ĳ' => 'IJ', 'ĳ' => 'ij', 'Ĵ' => 'J', 'ĵ' => 'j', 'Ķ' => 'K', 'ķ' => 'k', 'ĸ' => 'k', 'Ĺ' => 'L', 'ĺ' => 'l', 'Ļ' => 'L', 'ļ' => 'l', 'Ľ' => 'L', 'ľ' => 'l', 'Ŀ' => 'L', 'ŀ' => 'l', 'Ł' => 'L', 'ł' => 'l', 'Ń' => 'N', 'ń' => 'n', 'Ņ' => 'N', 'ņ' => 'n', 'Ň' => 'N', 'ň' => 'n', 'ŉ' => 'N', 'Ŋ' => 'n', 'ŋ' => 'N', 'Ō' => 'O', 'ō' => 'o', 'Ŏ' => 'O', 'ŏ' => 'o', 'Ő' => 'O', 'ő' => 'o', 'Œ' => 'OE', 'œ' => 'oe', 'Ø' => 'O', 'ø' => 'o', 'Ŕ' => 'R', 'ŕ' => 'r', 'Ŗ' => 'R', 'ŗ' => 'r', 'Ř' => 'R', 'ř' => 'r', 'Ś' => 'S', 'ś' => 's', 'Ŝ' => 'S', 'ŝ' => 's', 'Ş' => 'S', 'ş' => 's', 'Š' => 'S', 'š' => 's', 'Ţ' => 'T', 'ţ' => 't', 'Ť' => 'T', 'ť' => 't', 'Ŧ' => 'T', 'ŧ' => 't', 'Ũ' => 'U', 'ũ' => 'u', 'Ū' => 'U', 'ū' => 'u', 'Ŭ' => 'U', 'ŭ' => 'u', 'Ů' => 'U', 'ů' => 'u', 'Ű' => 'U', 'ű' => 'u', 'Ų' => 'U', 'ų' => 'u', 'Ŵ' => 'W', 'ŵ' => 'w', 'Ŷ' => 'Y', 'ŷ' => 'y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'ź' => 'z', 'Ż' => 'Z', 'ż' => 'z', 'Ž' => 'Z', 'ž' => 'z', 'ſ' => 's', '€' => 'E', '£' => '',];
        
        $string = strtr($string, $ACCENTED_CHARACTERS);

        $characters = [];

        // Assume ISO-8859-1 if not UTF-8
        $characters['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158).chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194).chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202).chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210).chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218).chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227).chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235).chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243).chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251).chr(252).chr(253).chr(255);
        $characters['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';

        $string = strtr($string, $characters['in'], $characters['out']);

        $doubleChars = [];

        $doubleChars['in'] = [
            chr(140),
            chr(156),
            chr(198),
            chr(208),
            chr(222),
            chr(223),
            chr(230),
            chr(240),
            chr(254),
        ];

        $doubleChars['out'] = ['OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th'];

        $string = str_replace($doubleChars['in'], $doubleChars['out'], $string);

        return $string;
    }
}

