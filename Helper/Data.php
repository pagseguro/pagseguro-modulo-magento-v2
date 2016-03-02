<?php
/**
 * 2007-2016 [PagSeguro Internet Ltda.]
 *
 * NOTICE OF LICENSE
 *
 *Licensed under the Apache License, Version 2.0 (the "License");
 *you may not use this file except in compliance with the License.
 *You may obtain a copy of the License at
 *
 *http://www.apache.org/licenses/LICENSE-2.0
 *
 *Unless required by applicable law or agreed to in writing, software
 *distributed under the License is distributed on an "AS IS" BASIS,
 *WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *See the License for the specific language governing permissions and
 *limitations under the License.
 *
 *  @author    PagSeguro Internet Ltda.
 *  @copyright 2016 PagSeguro Internet Ltda.
 *  @license   http://www.apache.org/licenses/LICENSE-2.0
 */

namespace UOL\PagSeguro\Helper;

/**
 * Class Data
 * @package UOL\PagSeguro\Helper
 */
class Data
{

    /**
     * @var Array
     * @enum
     */
    private $statusList = array(
        0 => "pagseguro_iniciado",
        1 => "pagseguro_aguardando_pagamento",
        2 => "pagseguro_em_analise",
        3 => "pagseguro_paga",
        4 => "pagseguro_disponivel",
        5 => "pagseguro_em_disputa",
        6 => "pagseguro_devolvida",
        7 => "pagseguro_cancelada",
        8 => "pagseguro_chargeback_debitado",
        9 => "pagseguro_em_contestacao"
    );

    /**
     * Concat char's in string.     *
     * @param string $value
     * @param int $length
     * @param string $endChars
     * @return string $value
     */
    public static function fixStringLength($value, $length, $endChars = '...')
    {
        if (!empty($value) and !empty($length)) {
            $cutLen = (int) $length - (int) strlen($endChars);
            if (strlen($value) > $length) {
                $strCut = substr($value, 0, $cutLen);
                $value = $strCut . $endChars;
            }
        }
        return $value;
    }
    /**
     * Convert value to float.
     * @param int $value
     * @return float $value
     */
    public static function toFloat($value)
    {
        return (float) $value;
    }

    /**
     * Treatment this address before being sent
     * @param string $fullAddress - Full address to treatment
     * @return array - Returns address of treatment in an array
     */
    public static function addressConfig($fullAddress)
    {
        $number  = 's/nº';
        $complement = '';
        $district = '';
        $broken = preg_split('/[-,\\n]/', $fullAddress);
        if (sizeof($broken) == 4) {
            list($address, $number, $complement, $district) = $broken;
        } elseif (sizeof($broken) == 3) {
            list($address, $number, $complement) = $broken;
        } elseif (sizeof($broken) == 2 || sizeof($broken) == 1) {
            list($address, $number, $complement) = self::sortData($fullAddress);
        } else {
            $address = $fullAddress;
        }
        return array(
            self::endTrim(substr($address, 0, 69)),
            self::endTrim($number),
            self::endTrim($complement),
            self::endTrim($district)
        );
    }

    /**
     * Remove the space at the end of the phrase, cut a piece of the phrase
     * @param string $e - Data to be ordained
     * @return Returns the phrase removed last  space, or a piece of phrase
     */
    private static function endTrim($e)
    {
        return preg_replace('/^\W+|\W+$/', '', $e);
    }
    /**
     * Sort the data reported
     * @param string $text - Text to be ordained
     * @return array - Returns an array with the sorted data
     */
    private static function sortData($text)
    {
        if (preg_match('/[-,\\n]/', $text)) {
            $broken = preg_split('/[-,\\n]/', $text);
            for ($i = 0; $i < strlen($broken[0]); $i++) {
                if (is_numeric(substr($broken[0], $i, 1))) {
                    return array(
                        substr($broken[0], 0, $i),
                        substr($broken[0], $i),
                        $broken[1]
                    );
                }
            }
        }
        $text = preg_replace('/\s/', ' ', $text);
        $find = substr($text, -strlen($text));
        for ($i  =0; $i < strlen($text); $i++) {
            if (is_numeric(substr($find, $i, 1))) {
                return array(
                    substr($text, 0, -strlen($text)+$i),
                    substr($text, -strlen($text)+$i),
                    ''
                );
            }
        }
        return array($text, '', '');
    }

    /**
     * Remove all non-numeric characters from Postal Code.
     * @return fixedPostalCode
     */
    public static function fixPostalCode($postalCode)
    {
        return preg_replace("/[^0-9]/", "", $postalCode);
    }

    /**
     * @return string
     */
    public static function generateStoreReference()
    {
        return substr(hash('sha256', uniqid(rand(), true)), 0, 7);
    }

    /**
     * @return string
     */
    public static function getStoreReference($reference, $order)
    {
        return $reference.$order;
    }

    /**
     * Decrypt a reference and returns the reference order identifier
     * @param string $reference
     * @return string
     */
    public function getReferenceDecryptOrderID($reference)
    {
        return str_replace(substr($reference, 0, 7), '', $reference);
    }

    /**
     * Get the name of payment status
     * @param Integer $key
     * @return multitype:|boolean
     */
    public function getStatusFromKey($key)
    {
        if (array_key_exists($key, $this->statusList)) {
            return $this->statusList[$key];
        }
        return false;
    }
}
