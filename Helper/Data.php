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
     * @var array
     * @enum
     */
    private static $statusList = array(
        0 => "pagseguro_iniciado",
        1 => "pagseguro_aguardando_pagamento",
        2 => "pagseguro_em_analise",
        3 => "pagseguro_paga",
        4 => "pagseguro_disponivel",
        5 => "pagseguro_em_disputa",
        6 => "pagseguro_devolvida",
        7 => "pagseguro_cancelada",
        8 => "pagseguro_chargeback_debitado",
        9 => "pagseguro_em_contestacao",
        10 => "partially_refunded"
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
    public static function getOrderStoreReference($reference, $order)
    {
        return $reference.$order;
    }

    /**
     * Decrypt a reference and returns the reference string
     * @param string $reference
     * @return string
     */
    public static function getReferenceDecrypt($reference)
    {
        return substr($reference, 0, 7);
    }

    /**
     * Decrypt a reference and returns the reference order identifier
     * @param string $reference
     * @return string
     */
    public static function getReferenceDecryptOrderID($reference)
    {
        return str_replace(substr($reference, 0, 7), '', $reference);
    }

    /**
     * Get the name of payment status
     * @param Integer $key
     * @return boolean|string
     */
    public static function getStatusFromKey($key)
    {
        if (array_key_exists($key, self::$statusList)) {
            return self::$statusList[$key];
        }
        return false;
    }


    /**
     * @param $status
     * @return mixed
     */
    public static function getKeyFromStatus($status)
    {
        return array_search($status, self::$statusList);
    }

    /**
     * Get the name to string of payment status
     * @param Integer $key
     * @return string|boolean
     */
    public static function getPaymentStatusToString($key)
    {

        if (array_key_exists($key, self::$statusList)) {
            switch ($key) {
                case 0:
                    return 'Pendente';
                    break;
                case 1:
                    return 'Aguardando pagamento';
                    break;
                case 2:
                    return 'Em an&aacute;lise';
                    break;
                case 3:
                    return 'Paga';
                    break;
                case 4:
                    return 'Dispon&iacute;vel';
                    break;
                case 5:
                    return 'Em disputa';
                    break;
                case 6:
                    return 'Devolvida';
                    break;
                case 7:
                    return 'Cancelada';
                    break;
                case 8:
                    return 'Chargeback Debitado';
                    break;
                case 9:
                    return 'Em Contestação';
                    break;
            }
        }
        return false;
    }

    /**
     * Format string phone number
     * @param string $phone
     * @return array of area code and number
     */
    public static function formatPhone($phone)
    {
        $ddd = '';
        $phone = self::keepOnlyNumbers($phone);

        // removes leading zero
        if (substr($phone, 0, 1) == 0) {
            $phone = substr($phone, 1);
        }

        // verifies if have ddd
        if (strlen($phone) > 9) {    
            //removes country identifier
            if (strlen($phone) > 11) {
                $phone = substr($phone, 2);
            }
            $ddd = substr($phone, 0, 2);
            $phone = substr($phone, 2);
        }

        return ['areaCode' => $ddd, 'number' => $phone];
    }

    /**
     * Remove especial characters and keep only numbers of the $document and
     * returns it and his type. If it is not a CPF or CNPJ size, 
     * throws an exception
     *
     * @param string $document
     * @return array
     * @throws Exception Invalid document
    */
    public static function formatDocument($document)
    {
       $document = self::keepOnlyNumbers($document);
       switch (strlen($document)) {
            case 14:
                return ['number' => $document, 'type' => 'cnpj'];
                break;
            case 11:
                return ['number' => $document, 'type' => 'cpf'];
                break;
            default:
                throw new \Exception('Invalid document');
                break;
        }
    }

    /**
     * Remove empty spaces and special characters, returning 
     * only the numbers of the $data
     *
     * @param   string $data
     * @return  string
    */
    public static function keepOnlyNumbers($data)
    {
        return preg_replace('/[^0-9]/', '', $data);
    }

    /**
     * @param $type
     * @return bool|string
     */
    public static function getTitleCancellationSourceTransaction($cancellationSource)
    {
        if (!is_null($cancellationSource)) {
            switch ($cancellationSource) {
                case "INTERNAL":
                    return 'PagSeguro';
                    break;
                case "EXTERNAL":
                    return 'Instituições Financeiras';
                    break;
                default:
                    return $cancellationSource;
                    break;
            }
        }

        return false;
    }

    /**
     * Translates the transaction type name to his respective name, according with the api
     *
     * @param int $transactionTypeCode
     * @return mixed string | int
     */
    public static function getTransactionTypeName($transactionTypeCode)
    {
        if ($transactionTypeCode) {
            switch ($transactionTypeCode) {
                case 1:
                    return 'Venda';
                    break;
                case 2:
                    return 'Transferência';
                    break;
                case 3:
                    return 'Adição de fundos';
                    break;
                case 4:
                    return 'Saque';
                    break;
                case 5:
                    return 'Cobrança';
                    break;
                case 6:
                    return 'Doação';
                    break;
                case 7:
                    return 'Bônus';
                    break;
                case 8:
                    return 'Repasse de bônus';
                    break;
                case 9:
                    return 'Operacional';
                    break;
                case 10:
                    return 'Doação pública';
                    break;
                case 11:
                    return 'Pagamento pré aprovado';
                    break;
                case 12:
                    return 'Campanha bônus';
                    break;
                case 13:
                    return 'Secundária';
                    break;
                case 14:
                    return 'Validador';
                    break;
                default:
                    return $transactionTypeCode;
                    break;
            }
        }
    }

    /**
     * Translates the transaction payment method type title to his respective name, according with the api
     * @param $type
     *
     * @return bool|string
     */
    public static function getTitleTypePaymentMethod($type)
    {
        if (!is_null($type)) {
            switch ($type) {
                case 1:
                    return 'Cartão de crédito';
                    break;
                case 2:
                    return 'Boleto';
                    break;
                case 3:
                    return 'Débito online(TEF)';
                    break;
                case 4:
                    return 'Saldo PagSeguro';
                    break;
                case 7:
                    return 'Depósito em conta';
                    break;
                default:
                    return $type;
                    break;
            }
        }
        return false;
    }

    /**
     * Translates the transaction payment method code title to his respective name, according with the api
     * @param $code
     *
     * @return bool|string
     */
    public static function getTitleCodePaymentMethod($code)
    {
        if (!is_null($code)) {
            switch ($code) {
                case 101:
                    return 'Cartão de crédito Visa';
                    break;
                case 102:
                    return 'Cartão de crédito MasterCard';
                    break;
                case 103:
                    return 'Cartão de crédito American Express';
                    break;
                case 104:
                    return 'Cartão de crédito Diners';
                    break;
                case 105:
                    return 'Cartão de crédito Hipercard';
                    break;
                case 106:
                    return 'Cartão de crédito Aura';
                    break;
                case 107:
                    return 'Cartão de crédito Elo';
                    break;
                case 109:
                    return 'Cartão de crédito PersonalCard';
                    break;
                case 112:
                    return 'Cartão de crédito BrasilCard';
                    break;
                case 113:
                    return 'Cartão de crédito FORTBRASIL';
                    break;
                case 115:
                    return 'Cartão de crédito VALECARD';
                    break;
                case 116:
                    return 'Cartão de crédito Cabal';
                    break;
                case 117:
                    return 'Cartão de crédito Mais!';
                    break;
                case 119:
                    return 'Cartão de crédito GRANDCARD';
                    break;
                case 120:
                    return 'Cartão de crédito Sorocred';
                    break;
                case 122:
                    return 'Cartão de crédito Up Policard';
                    break;
                case 123:
                    return 'Cartão de crédito Banese Card';
                    break;
                case 201:
                    return 'Boleto Bradesco';
                    break;
                case 202:
                    return 'Boleto Santander';
                    break;
                case 301:
                    return 'Débito online Bradesco';
                    break;
                case 302:
                    return 'Débito online Itaú';
                    break;
                case 304:
                    return 'Débito online Banco do Brasil';
                    break;
                case 306:
                    return 'Débito online Banrisul';
                    break;
                case 401:
                    return 'Saldo PagSeguro';
                    break;
                case 701:
                    return 'Depósito em conta - Banco do Brasil';
                    break;
                default:
                    return $code;
                    break;
            }
        }
        return false;
    }

}
