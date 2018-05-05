<?php

/**
 * Base conversions class.
 *
 * If you require arbitrarily large numbers, use methods prefixed with bc.
 *
 * Commonly called a Base N Converter (Base N).
 *
 * Allows converting between arbitrary bases in positional numeral systems, given arbitrary numerals.
 * To use BCMath, flag Havoc_Base->flagUseArbitraryPrecision(true).
 *
 * If you want to convert between many bases and not just an arbitrary A and an arbitrary B base,
 * then select a mid-way base (such as decimal) and instantiate this class for each base you wish to use,
 * where Base A is your mid-way base, and Base B is the base you wish to convert to.
 * Then use the mid-way base to convert from Base X to Base Mid-Way to Base Y.
 *
 * This is intended to work with CodeIgniter 3 as a library,
 * so the constructor takes an array of parameters.
 *
 * Provide one parameter with a key named base_a_numerals, and an auto-indexed array of the numerals.
 * Do the same for an array key named base_b_numerals.
 *
 * Negative integers are now supported. May affect fractional rounding.
 *
 * Flag the parameter $strip_zeros as true to remove all numerals of zero from numbers.
 *
 * Note: if strip zeros is false, and format numeric is true, the numeric formatter will render
 * seperators on the zeros, causing an output such as: "0,100".
 *
 * The term Base X refers to either base A or base B internally.
 *
 * Here is an example of params to the constructor.
 * $params = [
 *  base_a_numerals => [ "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ],
 *  base_b_numerals => [ "X", "E", "D", "T", "N", "F", "H", "K", "V", "L", "A", "Q" ],
 *  base_a_orderscount => 3,
 *  base_b_orderscount => 3,
 *  base_a_ordersseparator => ",",
 *  base_b_ordersseparator => " ",
 *  base_a_fractionaldelimiter => ".",
 *  base_b_fractionaldelimiter => ";",
 *  use_arbitrary_precision => true
 * ]
 *
 * - base_a_numerals: Numerals for Base A.
 * - base_b_numerals: Numerals for Base B.
 * - base_a_orderscount: Digit grouping count when rendering numbers with formatting. Example: EXXX -> E XXX
 * - base_b_orderscount: Digit grouping count when rendering numbers with formatting. Example: 1000 -> 1,000
 * - base_a_ordersseparator: Digit grouping separator. See above.
 * - base_b_ordersseparator: Digit grouping separator. See above.
 * - base_a_fractionaldelimiter: Delimiter for fractions. Example: X + N -> X;N
 * - base_b_fractionaldelimiter: Delimiter for fractions. Example: 0 + 33 -> 0.33
 * - use_arbitrary_precision: If true, module use BCMath for arbitrarily large numbers.
 *
 * Use Havoc_Base->setUseArbitraryPrecision([bool]) to determine whether or not to use BCMath arbitrary precision.
 * Use Havoc_Base->convertAb() to convert base A into base B.
 * Use Havoc_Base->convertBa() to convert base B into base A.
 * Use Havoc_Base->formatBaseANumber() to format a given number in the provided numeric format. I.e. EXXX -> E XXX.
 * Use Havoc_Base->formatBaseBNumber() to format a given number in the provided numeric format. I.e. 1000 -> 1,000.
 *
 * Once setup, you may provide numbers in the format given (except without an orders separator).
 * I.e. giving EXXX;H to convert into 1728.5.
 *
 * Fractional conversions comply with the precision provided. 0.500 will yield X;HXX.
 *
 * Both take the following flags:
 * - convert_to_base_numerals: true returns the result in the appropriate numerals instead of decimal numbers
 * that represent the index of the those numerals.
 *
 * - return_array: true returns an array. False returns a concatenated string of the numerals.
 *
 * - format_numeric: true returns a numerically formatted string, including 'thousands' separators.
 * 
 * @todo Implement arbitrarily precise fractions (v2.6.1)
 * @todo Implement scientific notation (v2.6)
 * @todo Implement short notation (v2.6.5)
 *
 * @author Kessie Heldieheren <me@kessie.gold>
 * @package Havoc
 * @version 2.6.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
class Havoc_Base
{
    // <editor-fold desc="<System Messages>" defaultstate="collapsed">
    /* Class messages (titles) */
    const EF_CANNOT_INSTANTIATE_CLASS = "Cannot instantiate HavocBase because %s.";
    const EF_CANNOT_CONVERT_FROM_BASE = "Cannot convert anything from a base because %s.";
    const EF_CANNOT_SET_BASE_A_NUMERALS = "Cannot set %s's numerals list because %s.";
    const EF_CANNOT_SET_BASE_B_NUMERALS = "Cannot set %s's numerals list because %s.";
    const EF_CANNOT_CONVERT_XY = "Cannot begin a conversion from %s to %s because %s.";
    const EF_CANNOT_INTDIV = "Cannot perform division on the input because %s.";
    const EF_CANNOT_FORMAT_X = "Cannot render %s number formatted because %s.";

    /* Class messages (messages) */
    const E_BASE_A_NUMERALS_EMPTY = "the first parameter is empty or not an array";
    const E_DIGITS_ARRAY_EMPTY = "the array of digits is empty";
    const E_NUMERALS_LIST_NOT_UNIQUE = "the numerals list contains duplicate symbols";
    const E_NO_NUMBER = "no number was provided";
    const E_NUMERALS_INVALID = "the number provided is not using the correct numerals";
    const E_NUMBER_TOO_LONG = "the number provided is too long";
    // </editor-fold>

    // <editor-fold desc="<Properties>" defaultstate="collapsed">
    /**
     * Flagging this as true will use BCMath for arbitrary number precision.
     *
     * @var bool
     */
    private $useArbitraryPrecision = false;

	/**
	 * Sets the maximum length of a number input.
	 *
	 * Defaults to 4096, as after this length it may take over a second to generate a number.
	 *
	 * @var int
	 */
    private $maxNumberLength = 4096;

    /**
     * Base A's name.
     *
     * @var string
     */
    private $baseAName = "Base A";

    /**
     * Base A radix value.
     *
     * @var int
     */
    private $baseARadix = 0;

    /**
     * Base A numerals list.
     *
     * @var array
     */
    private $baseANumerals = [];

    /**
     * Base A's orders separator.
     *
     * Note: this is equivalent to a decimal thousands separator.
     *
     * @var string
     */
    private $baseAOrdersSeperator = " ";

    /**
     * Base A's fractional delimiter.
     *
     * @var string
     */
    private $baseAFractionalDelimiter = ";";

    /**
     * Base A's negative sign.
     *
     * @var string
     */
    private $baseANegativeSign = "-";

    /**
     * After how many orders to place a separator.
     *
     * Note: this is equivalent to a decimal thousands separator.
     *
     * @var int
     */
    private $baseAOrdersCount = 3;

    /**
     * Base B's name.
     *
     * @var string
     */
    private $baseBName = "Base B";

    /**
     * Base B radix value.
     *
     * @var int
     */
    private $baseBRadix = 10;

    /**
     * Base B numerals list.
     *
     * @var array
     */
    private $baseBNumerals = [
        0,
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9
    ];

    /**
     * Base B's orders separator.
     *
     * Note: this is equivalent to a decimal thousands separator.
     *
     * @var string
     */
    private $baseBOrdersSeperator = ",";

    /**
     * Base B's fractional delimiter.
     *
     * @var string
     */
    private $baseBFractionalDelimiter = ".";

    /**
     * Base B's negative sign.
     *
     * @var string
     */
    private $baseBNegativeSign = "-";

    /**
     * After how many orders to place a separator.
     *
     * Note: this is equivalent to a decimal thousands separator.
     *
     * @var int
     */
    private $baseBOrdersCount = 3;
    // </editor-fold>

    // <editor-fold desc="<Constructor>" defaultstate="collapsed">
    /**
     * HavocBase constructor.
     *
     * @param array $params
     * @throws RuntimeException
     */
    public function __construct(array $params)
    {
        try {
            # If no Base A numerals provided. This does not have a default. Must be declared.
            if (empty($params["base_a_numerals"])) {
                throw new OutOfBoundsException(self::E_BASE_A_NUMERALS_EMPTY);
            }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_INSTANTIATE_CLASS,
                    $re->getMessage()
                )
            );
        }

        # Setup Base A.
        $this->setBaseANumerals($params["base_a_numerals"]);
        $this->setBaseARadix();

        # Setup Base B if provided. Default is decimal.
        if (!empty($params["base_b_numerals"])) {
            $this->setBaseBNumerals($params["base_b_numerals"]);
            $this->setBaseBRadix();
        }

        # Set Base A name if provided.
        if (!empty($params["base_a_name"])) {
            $this->setBaseAName($params["base_a_name"]);
        }

        # Set Base B name if provided.
        if (!empty($params["base_b_name"])) {
            $this->setBaseBName($params["base_b_name"]);
        }

        # Set Base A orders count if provided.
        if (!empty($params["base_a_orderscount"])) {
            $this->setBaseAOrdersCount($params["base_a_orderscount"]);
        }

        # Base Base B orders count if provided.
        if (!empty($params["base_b_orderscount"])) {
            $this->setBaseBOrdersCount($params["base_b_orderscount"]);
        }

        # Set Base A orders separator if provided.
        if (!empty($params["base_a_ordersseparator"])) {
            $this->setBaseAOrdersSeperator($params["base_a_ordersseparator"]);
        }

        # Set Base B orders separator if provided.
        if (!empty($params["base_b_ordersseparator"])) {
            $this->setBaseBOrdersSeperator($params["base_b_ordersseparator"]);
        }

        # Set Base A fractional delimiter if provided.
        if (!empty($params["base_a_fractionaldelimiter"])) {
            $this->setBaseAFractionalDelimiter($params["base_a_fractionaldelimiter"]);
        }

        # Set Base B fractional delimiter if provided.
        if (!empty($params["base_b_fractionaldelimiter"])) {
            $this->setBaseBFractionalDelimiter($params["base_b_fractionaldelimiter"]);
        }

        # Set Base A negative sign f provided.
        if (!empty($params["base_a_negativesign"])) {
            $this->setBaseANegativeSign($params["base_a_negativesign"]);
        }

        # Set Base B negative sign if provided.
        if (!empty($params["base_b_negativesign"])) {
            $this->setBaseBNegativeSign($params["base_b_negativesign"]);
        }

        if (!empty($params["use_arbitrary_precision"])) {
            $this->setUseArbitraryPrecision($params["use_arbitrary_precision"]);
        }

	    if (!empty($params["max_number_length"])) {
		    $this->setMaxNumberLength($params["max_number_length"]);
	    }
    }
    // </editor-fold>

    // <editor-fold desc="<Main Methods: Conversion>" defaultstate="collapsed">
    /**
     * Converts a number in base A into base B, using Base B's numerals instead of an integer.
     *
     * Method also takes fractions, formatted as prescribed. This method also has provisions for input given
     * using the number format provided.
     *
     * Note: if strip zeros is false, and format numeric is true, the numeric formatter will render
     * seperators on the zeros, causing an output such as: "0,100".
     *
     * @param string $number
     * @param bool $convert_to_base_numerals
     * @param bool $return_array
     * @param bool $format_numeric
     * @param bool $strip_zeros
     * @return array|string
     */
    public function convertAb(
        string $number,
        bool $convert_to_base_numerals = true,
        bool $return_array = true,
        bool $format_numeric = false,
        bool $strip_zeros = true
    ) {
        return $this->convertXy(
            $number,
            true,
            $this->getBaseAName(),
            $this->getBaseBName(),
            $this->getBaseAFractionalDelimiter(),
            $this->getBaseBFractionalDelimiter(),
            $this->getBaseANegativeSign(),
            $this->getBaseBNegativeSign(),
            $this->getBaseAOrdersSeperator(),
            $this->getBaseARadix(),
            $this->getBaseBRadix(),
            $this->getBaseANumerals(),
            $this->getBaseBNumerals(),
            $convert_to_base_numerals,
            $return_array,
            $format_numeric,
            $strip_zeros
        );
    }

    /**
     * Converts a number in base A into base B.
     *
     * @param array $number
     * @param bool $convert_to_base_numerals
     * @param bool $return_array
     * @param bool $format_numeric
     * @return array|string
     */
    private function convertArrayAb(
        array $number,
        bool $convert_to_base_numerals = true,
        bool $return_array = true,
        bool $format_numeric = false
    ) {
        # Convert the indices of the radix into the target base.
        $result = $this->convertBaseAb($number);

        # If the result should be indices of the radix, or converted from their indices into the respective numerals.
        if ($convert_to_base_numerals) {
            $result = $this->renderDigitsInBaseBNumerals($result);
        }

        # If the result should be numerically formatted, as prescribed by the user.
        if ($format_numeric) {
            $result = $this->formatBaseBNumeric($result);
        }

        # If an array should be returned.
        if ($return_array) {
            return $result;
        }

        # Return string.
        return implode("", $result);
    }

    /**
     * Converts a number in base B into base A, using Base A's numerals instead of an integer.
     *
     * Method also takes fractions, formatted as prescribed. This method also has provisions for input given
     * using the number format provided.
     *
     * Note: if strip zeros is false, and format numeric is true, the numeric formatter will render
     * seperators on the zeros, causing an output such as: "0,100".
     *
     * @param string $number
     * @param bool $convert_to_base_numerals
     * @param bool $return_array
     * @param bool $format_numeric
     * @param bool $strip_zeros
     * @return array|string
     */
    public function convertBa(
        string $number,
        bool $convert_to_base_numerals = true,
        bool $return_array = true,
        bool $format_numeric = false,
        bool $strip_zeros = true
    ) {
        return $this->convertXy(
            $number,
            false,
            $this->getBaseBName(),
            $this->getBaseAName(),
            $this->getBaseBFractionalDelimiter(),
            $this->getBaseAFractionalDelimiter(),
            $this->getBaseBNegativeSign(),
            $this->getBaseANegativeSign(),
            $this->getBaseBOrdersSeperator(),
            $this->getBaseBRadix(),
            $this->getBaseARadix(),
            $this->getBaseBNumerals(),
            $this->getBaseANumerals(),
            $convert_to_base_numerals,
            $return_array,
            $format_numeric,
            $strip_zeros
        );
    }

    /**
     * Converts a number in base B into base A.
     *
     * @param array $number
     * @param bool $convert_to_base_numerals
     * @param bool $return_array
     * @param bool $format_numeric
     * @return array|string
     */
    private function convertArrayBa(
        array $number,
        bool $convert_to_base_numerals = true,
        bool $return_array = true,
        bool $format_numeric = false
    ) {
        # Convert the indices of the radix into the target base.
        $result = $this->convertBaseBa($number);

        # If the result should be indices of the radix, or converted from their indices into the respective numerals.
        if ($convert_to_base_numerals) {
            $result = $this->renderDigitsInBaseANumerals($result);
        }

        # If the result should be numerically formatted, as prescribed by the user.
        if ($format_numeric) {
            $result = $this->formatBaseANumeric($result);
        }

        # If an array should be returned.
        if ($return_array) {
            return $result;
        }

        # Return string.
        return implode("", $result);
    }

    /**
     * Convert a number in Base X into Base Y.
     *
     * Note: if strip zeros is false, and format numeric is true, the numeric formatter will render
     * seperators on the zeros, causing an output such as: "0,100".
     *
     * @param string $number
     * @param bool $is_base_a
     * @param string $host_name
     * @param string $target_name
     * @param string $host_delimiter
     * @param string $target_delimiter
     * @param string $host_negative
     * @param string $target_negative
     * @param string $host_orders_seperator
     * @param int $host_base
     * @param int $target_base
     * @param array $host_numerals
     * @param array $target_numerals
     * @param bool $convert_to_base_numerals
     * @param bool $return_array
     * @param bool $format_numeric
     * @param bool $strip_zeros
     * @return array|string
     * @throws RuntimeException
     */
    private function convertXy(
        string $number,
        bool $is_base_a,
        string $host_name,
        string $target_name,
        string $host_delimiter,
        string $target_delimiter,
        string $host_negative,
        string $target_negative,
        string $host_orders_seperator,
        int $host_base,
        int $target_base,
        array $host_numerals,
        array $target_numerals,
        bool $convert_to_base_numerals,
        bool $return_array,
        bool $format_numeric,
        bool $strip_zeros
    ) {
        # Radix, delimiter, separator, and negative sign to provide a list of valid input.
        $valid_numerals = array_merge(
            $host_numerals,
            [$host_delimiter, $host_orders_seperator, $host_negative]
        );

        # Initiate is_negative. True if number is negative.
        $is_negative = false;

        # Remove negative sign if the number is negative.
        if ($this->isNegativeString($number, $host_negative)) {
        	# Trim negative sign from number.
            $number = ltrim($number, $host_negative);

            # Declare the number as negative.
            $is_negative = true;
        }

        # Strip zeroes from input.
        if ($strip_zeros) {
            $number = ltrim($number, $host_numerals[0]);
        }

        # The entire number split into an array.
        $number_as_array = str_split($number);

        try {
            # If an empty string is given as an input. This is not a valid number.
            if ("" === $number) {
                throw new OutOfBoundsException(self::E_NO_NUMBER);
            }

            # If an input is not a valid host base number (including formatting).
            if (false === $this->validateNumberString($number_as_array, $valid_numerals)) {
                throw new OutOfBoundsException(self::E_NUMERALS_INVALID);
            }

            # If number input exceeds maximum number length.
	        if (count($number_as_array) > $this->getMaxNumberLength()) {
		        throw new OutOfBoundsException(self::E_NUMBER_TOO_LONG);
	        }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_CONVERT_XY,
                    $host_name,
                    $target_name,
                    $re->getMessage()
                )
            );
        }

        # Split input into its components:-
        # [0] = whole number component.
        # [1] = fraction component if provided.
        $components = $this->splitNumberIntoComponents($number, $host_delimiter);

        # If a fractional element was given as input.
        if (isset($components[1])) {
            # Fractional element split into an array.
            $fraction = str_split($components[1]);

            # Fractional element rendered as indices of its radix.
            $fraction_indices = $this->renderNumeralsInBaseXIndices($fraction, $host_numerals);

            # Fractional precision (a.k.a decimal precision) of the fractional element.
            $resolution = count($fraction_indices);

            if ($this->getUseArbitraryPrecision()) {
                # Fractional element converted from its base as indices into a decimal fraction.
                $base_a_to_dec = $this->bcConvertFractionBaseToDec($fraction_indices, (string) $host_base);

                # Decimal of previous result converted into the target base as indices of its radix.
                $result_fraction = $this->renderDigitsInBaseXNumerals(
                    $this->bcConvertFractionDecToBase((string) $base_a_to_dec, $resolution, (string) $target_base),
                    $target_numerals
                );
            } else {
                # Fractional element converted from its base as indices into a decimal fraction.
                $base_a_to_dec = $this->convertFractionBaseToDec($fraction_indices, $host_base);

                # Decimal of previous result converted into the target base as indices of its radix.
                $result_fraction = $this->renderDigitsInBaseXNumerals(
                    $this->convertFractionDecToBase($base_a_to_dec, $resolution, $target_base),
                    $target_numerals
                );
            }
        }

        # The basic number, stripped of the orders separators.
        $number = str_replace($host_orders_seperator, "", $components[0]);

        # Number element (integer) element split into an array.
        $number = str_split($number, 1);

        # Number element rendered as indices of its radix.
        $number_indices = $this->renderNumeralsInBaseXIndices($number, $host_numerals);

        # TODO Not having to have this if statement and instead integrate related methods.
        # Number element converted into the target base as indices of its radix.
        if ($is_base_a) {
            $result_number = $this->convertArrayAb(
                $number_indices,
                $convert_to_base_numerals,
                $return_array,
                $format_numeric
            );
        } else {
            $result_number = $this->convertArrayBa(
                $number_indices,
                $convert_to_base_numerals,
                $return_array,
                $format_numeric
            );
        }

        # Format number and return the result.
        if ($return_array) {
            # If number is negative.
            if ($is_negative) {
                # Prepend the target negative sign to result.
                array_unshift($result_number, $target_negative);
            }

            # If the fractional element is set.
            if (isset($result_fraction)) {
                # Return an array, merging the number and fractional element joined by the target base delimiter.
                return (array_merge($result_number, [$target_delimiter], $result_fraction));
            }

            # Return array result.
            return $result_number;
        }

        # If number is negative.
        if ($is_negative) {
            # Prepend the target negative sign to result.
            $result_number = $target_negative . $result_number;
        }

        # If the fractional element is set.
        if (isset($result_fraction)) {
            # Return string result with fractional element.
            return $result_number . $target_delimiter . implode("", $result_fraction);
        }

        # Return string result.
        return $result_number;
    }
    // </editor-fold>

    // <editor-fold desc="<Main Methods: Formatting>" defaultstate="collapsed">
    /**
     * Returns a raw Base A number numerically formatted.
     *
     * Note: if strip zeros is false, and format numeric is true, the numeric formatter will render
     * seperators on the zeros, causing an output such as: "0,100".
     *
     * @param string $number
     * @param bool $return_array
     * @param bool $strip_zeros
     * @return array|string
     */
    public function formatBaseANumber(string $number, bool $return_array = false, bool $strip_zeros = true)
    {
        return $this->formatBaseXNumber(
            $number,
            $this->getBaseAName(),
            $this->getBaseAFractionalDelimiter(),
            $this->getBaseANegativeSign(),
            $this->getBaseAOrdersCount(),
            $this->getBaseAOrdersSeperator(),
            $this->getBaseANumerals(),
            $return_array,
            $strip_zeros
        );
    }

    /**
     * Returns a raw Base B number numerically formatted.
     *
     * Note: if strip zeros is false, and format numeric is true, the numeric formatter will render
     * seperators on the zeros, causing an output such as: "0,100".
     *
     * @param string $number
     * @param bool $return_array
     * @param bool $strip_zeros
     * @return array|string
     */
    public function formatBaseBNumber(string $number, bool $return_array = false, bool $strip_zeros = true)
    {
        return $this->formatBaseXNumber(
            $number,
            $this->getBaseBName(),
            $this->getBaseBFractionalDelimiter(),
            $this->getBaseBNegativeSign(),
            $this->getBaseBOrdersCount(),
            $this->getBaseBOrdersSeperator(),
            $this->getBaseBNumerals(),
            $return_array,
            $strip_zeros
        );
    }

    /**
     * Returns a raw Base X number numerically formatted.
     *
     * Note: if strip zeros is false, and format numeric is true, the numeric formatter will render
     * seperators on the zeros, causing an output such as: "0,100".
     *
     * @param string $number
     * @param string $host_name
     * @param string $host_delimiter
     * @param string $host_negative
     * @param string $host_orders_count
     * @param string $host_orders_seperator
     * @param array $host_numerals
     * @param bool $return_array
     * @param bool $strip_zeros
     * @return array|string
     * @throws RuntimeException
     */
    public function formatBaseXNumber(
        string $number,
        string $host_name,
        string $host_delimiter,
        string $host_negative,
        string $host_orders_count,
        string $host_orders_seperator,
        array $host_numerals,
        bool $return_array,
        bool $strip_zeros
    ) {
        # Radix, delimiter, separator, and negative to provide a list of valid input.
        $valid_numerals = array_merge(
            $host_numerals,
            [$host_delimiter, $host_orders_seperator, $host_negative]
        );

        # Initiate is_negative. True if number is negative.
        $is_negative = false;

        # Remove negative sign if the number is negative.
        if ($this->isNegativeString($number, $host_negative)) {
	        # Trim negative sign from number.
	        $number = ltrim($number, $host_negative);

	        # Declare the number as negative.
	        $is_negative = true;
        }

        # Strip zeroes from input.
        if ($strip_zeros) {
            $number = ltrim($number, $host_numerals[0]);
        }

        # The entire number split into an array.
        $number_as_array = str_split($number);

        try {
            # If an empty string is given as an input. This is not a valid number.
            if ("" === $number) {
                throw new OutOfBoundsException(self::E_NO_NUMBER);
            }

            # If an input is not a valid host base number (including formatting).
            if (false === $this->validateNumberString($number_as_array, $valid_numerals)) {
                throw new OutOfBoundsException(self::E_NUMERALS_INVALID);
            }

	        # If number input exceeds maximum number length.
	        if (count($number_as_array) > $this->getMaxNumberLength()) {
		        throw new OutOfBoundsException(self::E_NUMBER_TOO_LONG);
	        }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_FORMAT_X,
                    $host_name,
                    $re->getMessage()
                )
            );
        }

        # Split input into its components:-
        # [0] = whole number component.
        # [1] = fraction component if provided.
        $components = $this->splitNumberIntoComponents($number, $host_delimiter);

        # If a fractional element was given as input.
        if (isset($components[1])) {
            $fraction = str_split($components[1]);
            $fraction_digits = $this->renderNumeralsInBaseXIndices($fraction, $host_numerals);
            $result_fraction = $this->renderDigitsInBaseXNumerals($fraction_digits, $host_numerals);
        }

        # The basic number, stripped of the orders separators.
        $number = str_replace($host_orders_seperator, "", $components[0]);

        # Number element (integer) element split into an array.
        $number = str_split($number, 1);

        # Number element rendered as indices of its radix.
        $number_indices = $this->renderNumeralsInBaseXIndices($number, $host_numerals);

        # Number element formatted.
        $result_number = $this->formatBaseXNumeric(
            $this->renderDigitsInBaseXNumerals($number_indices, $host_numerals),
            $host_orders_seperator,
            $host_orders_count
        );

        # If returning an array.
        if ($return_array) {
            # If the fractional element is set.
            if (isset($result_fraction)) {
                # If number is negative.
                if ($is_negative) {
                    array_unshift($result_number, $host_negative);
                }

                # Return an array, merging the number and fractional element joined by the target base delimiter.
                return (array_merge($result_number, [$host_delimiter], $result_fraction));
            }

            # If number is negative.
            if ($is_negative) {
                $result_number = $host_negative . $result_number;
            }

            # Return an array containing only the number element.
            return ($result_number);
        }

        # If the fractional element is set.
        if (isset($result_fraction)) {
            # If number is negative.
            if ($is_negative) {
                array_unshift($result_number, $host_negative);
            }

            # Return the number and fractional element as a string.
            return implode("", array_merge($result_number, [$host_delimiter], $result_fraction));
        }

        # If number is negative.
        if ($is_negative) {
            array_unshift($result_number, $host_negative);
        }

        # Return the number element as a string.
        return (implode("", $result_number));
    }
    // </editor-fold>

    // <editor-fold desc="<Fractions>" defaultstate="collapsed">
    /**
     * Convert a fraction in Base A to decimal.
     *
     * This is a preliminary conversion. Conversions are in two parts. See the method below this one.
     *
     * @param array $fraction
     * @param int $base
     * @return float
     */
    private function convertFractionBaseToDec(array $fraction = [], int $base): float
    {
        # Prevents a rounding error.
        array_push($fraction, 0);

        # Resolution of the fraction (a.k.a decimal precision).
        $resolution = count($fraction);

        # Iteration pointer. This is part of the equation below and power'd.
        # For every place we move we decrement this.
        $iteration = -1;

        # Equation pointer. Stores our value as we iterate every place.
        $pointer = 0;

        # Loop over the fraction as indices of its radix.
        foreach ($fraction as $index) {
            # (pointer + digit * base ^ iteration)
            $pointer = $pointer + $index * $base ** $iteration;

            # Decrement iteration value.
            $iteration--;
        }

        # Return a result rounded to the nearest decimal place.
        return round($pointer, $resolution);
    }

    /**
     * Convert a fraction in Base A to decimal.
     *
     * This is a preliminary conversion. Conversions are in two parts. See the method below this one.
     *
     * BCMath version.
     *
     * @param array $fraction
     * @param string $base
     * @return string
     */
    private function bcConvertFractionBaseToDec(array $fraction = [], string $base): string
    {
        # Prevents a rounding error.
        array_push($fraction, 0);

        # Resolution of the fraction (a.k.a decimal precision).
        $resolution = (string) count($fraction);

        # Iteration pointer. This is part of the equation below and power'd.
        # For every place we move we decrement this.
        $iteration = "-1";

        # Equation pointer. Stores our value as we iterate every place.
        $pointer = "0";

        # Loop over the fraction as indices of its radix.
        foreach ($fraction as $index) {
            # (pointer + digit * base ^ iteration)
            $pointer = (float) $pointer + $index * $base ** $iteration;

            # Decrement iteration value.
            $iteration = bcsub($iteration, 1);
        }

        # Return a result rounded to the nearest decimal place.
        return $this->bcround($pointer, $resolution);
    }

    /**
     * Convert a fraction in decimal to Base A.
     *
     * This is the final part of fraction conversions.
     *
     * @param float $fraction
     * @param int $resolution
     * @param int $base
     * @return array
     */
    private function convertFractionDecToBase(float $fraction, $resolution = 1, int $base): array
    {
        # Result as an empty array.
        $result = [];

        # Equation pointer. Stores our value as we iterate every place.
        # Defaulted as the fraction times the base before beginning the loop.
        $pointer = $fraction * $base;

        # For every decimal place given, do this loop.
        for ($i = 1; $i <= $resolution; $i++) {
            # If this is the first loop.
            if ($i === 1) {
                # Store the initial pointer value into the result floored.
                array_push($result, floor($pointer));
                continue;
            }

            # Value of the pointer truncated to its fractional element.
            $truncated = $pointer - (int) $pointer;

            # Next pointer in the loop is the truncated pointer times the base.
            $pointer = $truncated * $base;

            # If this loop is the final loop.
            if ($i === $resolution) {
                # Round up to acquire a resolved final fractional element.
                $resolved = round($pointer, 0, PHP_ROUND_HALF_UP);

                # If the resolved index rounds up to the increment of the base.
                if ($resolved >= $base) {
                    # Clamps the index back to a sane value.
                    # Prevents rounding, for example, a binary index of [1] up to [2]
                    # and attempting to display a binary numeral for [2], which does not exist.
                    $resolved = $resolved - 1;
                }

                # Push resolved final pointer into the result.
                array_push($result, $resolved);
                continue;
            }

            # Push the current pointer into the result floored.
            array_push($result, floor($pointer));
        }

        # Return the result.
        return $result;
    }

    /**
     * Convert a fraction in decimal to Base A.
     *
     * This is the final part of fraction conversions.
     *
     * BCMath version.
     *
     * @param string $fraction
     * @param int $resolution
     * @param string $base
     * @return array
     */
    private function bcConvertFractionDecToBase(string $fraction, $resolution = 1, string $base): array
    {
        # Result as an empty array.
        $result = [];

        # Equation pointer. Stores our value as we iterate every place.
        # Defaulted as the fraction times the base before beginning the loop.
        $pointer = $fraction * $base;

        # For every decimal place given, do this loop.
        for ($i = 1; $i <= $resolution; $i++) {
            # If this is the first loop.
            if ($i === 1) {
                # Store the initial pointer value into the result floored.
                array_push($result, floor($pointer));
                continue;
            }

            # Value of the pointer truncated to its fractional element.
            $truncated = $pointer - (int) $pointer;

            # Next pointer in the loop is the truncated pointer times the base.
            $pointer = $truncated * $base;

            # If this loop is the final loop.
            if ($i === $resolution) {
                # Round up to acquire a resolved final fractional element.
                $resolved = round($pointer, 0, PHP_ROUND_HALF_UP);

                # If the resolved index rounds up to the increment of the base.
                if ($resolved >= $base) {
                    # Clamps the index back to a sane value.
                    # Prevents rounding, for example, a binary index of [1] up to [2]
                    # and attempting to display a binary numeral for [2], which does not exist.
                    $resolved = $resolved - 1;
                }

                # Push resolved final pointer into the result.
                array_push($result, $resolved);
                continue;
            }

            # Push the current pointer into the result floored.
            array_push($result, floor($pointer));
        }

        # Return the result.
        return $result;
    }
    // </editor-fold>

    // <editor-fold desc="<Integers>" defaultstate="collapsed">
    /**
     * Convert a number to a specific base.
     *
     * @param float $number
     * @param int $base
     * @return array
     */
    private function convertToBase(float $number, int $base): array
    {
        # Indices of the radix (result).
        $indices = [];

        # While the number variable is greater than 0.
        while ($number > 0) {
            # Result is the number modulo the base.
            $result = $number % $base;

            # Push digit to result.
            array_push($indices, $result);

            # Divide the number by the base for the next loop. Loop ends if this is less than 0.
            # Use BCMath to avoid the errors here.
            $number = @intdiv($number, $base);
        }

        # If the digits list is empty.
        if (empty($indices)) {
            # Add an index of 0.
            $indices[0] = 0;
        }

        # Return the result reversed.
        return array_reverse($indices);
    }

    /**
     * Convert a number to a specific base.
     *
     * BCMath version.
     *
     * @param string $number
     * @param string $base
     * @return array
     */
    private function bcConvertToBase(string $number, string $base): array
    {
        # Indices of the radix (result).
        $indices = [];

        # While the number variable is greater than 0.
        while (bccomp($number, "0") === 1) {
            # Result is the number modulo the base.
            $result = (int) bcmod($number, $base);

            # Push digit to result.
            array_push($indices, $result);

            # Divide the number by the base for the next loop. Loop ends if this is less than 0.
            $number = (string) bcdiv($number, $base);
        }

        # If the digits list is empty.
        if (empty($indices)) {
            # Add an index of 0.
            $indices[0] = 0;
        }

        # Return the result reversed.
        return array_reverse($indices);
    }

    /**
     * Convert a number from a specific base.
     *
     * @param array $indices
     * @param int $base
     * @return int
     * @throws RuntimeException
     */
    private function convertFromBase(array $indices, int $base)
    {
        try {
            # If digits input is empty.
            if (empty($indices)) {
                throw new OutOfBoundsException(self::E_DIGITS_ARRAY_EMPTY);
            }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_CONVERT_FROM_BASE,
                    $re->getMessage()
                )
            );
        }

        # Initial number declaration.
        $result = 0;

        # For each digit provided.
        foreach ($indices as $index) {
            # Number is equal to the base times the number plus the digit given.
            $result = $base * $result + $index;
        }

        # Return the result.
        return $result;
    }

    /**
     * Convert a number from a specific base.
     *
     * BCMath version.
     *
     * @param array $indices
     * @param string $base
     * @return int
     * @throws RuntimeException
     */
    private function bcConvertFromBase(array $indices, string $base)
    {
        try {
            # If digits input is empty.
            if (empty($indices)) {
                throw new OutOfBoundsException(self::E_DIGITS_ARRAY_EMPTY);
            }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_CONVERT_FROM_BASE,
                    $re->getMessage()
                )
            );
        }

        # Base to string for BCM.
        $base = (string) $base;

        # Initial number declaration.
        $result ="0";

        # For each digit provided.
        foreach ($indices as $index) {
            # Number is equal to the base times the number plus the digit given.
            $result = bcadd(bcmul($base, $result), (string) $index);
        }

        # Return the result.
        return $result;
    }

    /**
     * Convert a number in base A into base B.
     *
     * @param array $indices
     * @return array
     */
    private function convertBaseAb(array $indices): array
    {
        # Radix of host base.
        $host_base = $this->getBaseARadix();

        # Radix of target base.
        $target_base = $this->getBaseBRadix();

        # If using arbitrary precision.
        if ($this->getUseArbitraryPrecision()) {
            # Digits converted from the host base.
            $converted = $this->bcConvertFromBase($indices, (string) $host_base);

            # Return digits converted into the target base.
            return(
                $this->bcConvertToBase(
                    $converted,
                    $target_base
                )
            );
        } else {
            # Digits converted from the host base.
            $converted = $this->convertFromBase($indices, $host_base);

            # Return digits converted into the target base.
            return(
                $this->convertToBase(
                    $converted,
                    $target_base
                )
            );
        }

    }

    /**
     * Convert a number in base B into Base A.
     *
     * @param array $indices
     * @return array
     */
    private function convertBaseBa(array $indices): array
    {
        # Radix of host base.
        $host_base = $this->getBaseBRadix();

        # Radix of target base.
        $target_base = $this->getBaseARadix();

        # If using arbitrary precision.
        if ($this->getUseArbitraryPrecision()) {
            # Digits converted from the host base.
            $converted = $this->bcConvertFromBase($indices, (string) $host_base);

            # Return digits converted into the target base.
            return(
                $this->bcConvertToBase(
                    $converted,
                    $target_base
                )
            );
        } else {
            # Digits converted from the host base.
            $converted = $this->convertFromBase($indices, $host_base);

            # Return digits converted into the target base.
            return(
                $this->convertToBase(
                    $converted,
                    $target_base
                )
            );
        }
    }
    // </editor-fold>

    // <editor-fold desc="<Rendering>" defaultstate="collapsed">
    /**
     * Render given digits in their native Base A numerals.
     *
     * @param array $indices
     * @return array
     */
    protected function renderDigitsInBaseANumerals(array $indices): array
    {
        # Numerals of the host base.
        $numerals = $this->getBaseANumerals();

        # Return result.
        return $this->renderDigitsInBaseXNumerals($indices, $numerals);
    }

    /**
     * Render given digits in their native Base B numerals.
     *
     * @param array $indices
     * @return array
     */
    protected function renderDigitsInBaseBNumerals(array $indices): array
    {
        # Numerals of the host base.
        $numerals = $this->getBaseBNumerals();

        # Return result.
        return $this->renderDigitsInBaseXNumerals($indices, $numerals);
    }

    /**
     * Render given digits in Base X numerals.
     *
     * @param array $indices
     * @param array $numerals
     * @return array
     */
    private function renderDigitsInBaseXNumerals(array $indices, array $numerals): array
    {
        # Result.
        $result = [];

        # For each digit as a key and value pair.
        foreach ($indices as $key => $value) {
            # Gets the numeral related to the index of the radix and adds it to the result.
            array_push($result, $numerals[$value]);
        }

        # Returns the result.
        return $result;
    }

    /**
     * Render numbers in Base A using integers.
     *
     * @param array $indices
     * @return array
     */
    protected function renderNumeralsInBaseAIndices(array $indices): array
    {
        # Numerals of the host base.
        $numerals = $this->getBaseANumerals();

        # Return result.
        return $this->renderNumeralsInBaseXIndices($indices, $numerals);
    }

    /**
     * Render numbers in Base A using integers.
     *
     * @param array $indices
     * @return array
     */
    protected function renderNumeralsInBaseBIndices(array $indices): array
    {
        # Numerals of the host base.
        $numerals = $this->getBaseBNumerals();

        # Return result.
        return $this->renderNumeralsInBaseXIndices($indices, $numerals);
    }

    /**
     * Render numbers in Base X using integers.
     *
     * @param array $indices
     * @param array $numerals
     * @return array
     */
    private function renderNumeralsInBaseXIndices(array $indices, array $numerals): array
    {
        # Result.
        $result = [];

        # For each digit as a key and value pair.
        foreach ($indices as $key => $value) {
            # Acquire the index of the radix of the numeral being searched.
            $digit = array_search($value, $numerals);

            # Pushes the index of the numeral being searched to the result.
            array_push($result, $digit);
        }

        # Return result.
        return $result;
    }
    // </editor-fold>

    // <editor-fold desc="<Formatting>" defaultstate="collapsed">
    /**
     * Adds a space between every three digits in a Base A number.
     *
     * @param $digits
     * @return array
     */
    protected function formatBaseANumeric(array $digits): array
    {
        # Orders separator of the host base.
        $orders_separator = $this->getBaseAOrdersSeperator();

        # Orders count of the host base.
        $orders_count = $this->getBaseAOrdersCount();

        # Return formatted number.
        return $this->formatBaseXNumeric($digits, $orders_separator, $orders_count);
    }

    /**
     * Adds a space between every three digits in a Base B number.
     *
     * @param $digits
     * @return array
     */
    protected function formatBaseBNumeric(array $digits): array
    {
        # Orders separator of the host base.
        $orders_separator = $this->getBaseBOrdersSeperator();

        # Orders count of the host base.
        $orders_count = $this->getBaseBOrdersCount();

        # Return formatted number.
        return $this->formatBaseXNumeric($digits, $orders_separator, $orders_count);
    }

    /**
     * Adds a space between every three digits in a Base X number.
     *
     * @param array $numerals
     * @param string $orders_separator
     * @param int $orders_count
     * @return array
     */
    private function formatBaseXNumeric(array $numerals, string $orders_separator, int $orders_count): array
    {
        # Split the array into chunks, where the length of chunks is defined by the orders separator.
        # Then we reverse the array so that we can iterate over it and add the orders separator as we go.
        # If you do not reverse the array, you won't get accurate results for non-multiples of the order.
        $numerals = array_chunk(array_reverse($numerals), $orders_count);

        # Result.
        $result = [];

        # For each digit as a set.
        foreach ($numerals as $set) {
            # For each set as a digit.
            foreach ($set as $digit) {
                # Add the digit to the results.
                array_push($result, $digit);
            }

            # Add the orders separator after every X elements.
            array_push($result, $orders_separator);
        }

        # Keys of the results.
        $result_keys = array_keys($result);

        # Final key of the result.
        $result_end = end($result_keys);

        # If there is a trailing orders separator (i.e. "1,000,").
        if ($orders_separator === $result[$result_end]) {
            # Pop it off the array.
            array_pop($result);
        }

        # Return the resulting formatted number reversed back to its original order.
        return array_reverse($result);
    }
    // </editor-fold>

    // <editor-fold desc="<Miscellaneous>" defaultstate="collapsed">
    /**
     * Splits a number string into two parts: the units and fraction.
     *
     * @param $string
     * @param $delimiter
     * @return array
     */
    private function splitNumberIntoComponents($string, $delimiter): array
    {
        # Return the number split by its delimiter.
        return explode($delimiter, $string);
    }

    /**
     * Returns false if the number string given contains invalid numerals.
     *
     * @param array $number
     * @param array $numerals
     * @return bool
     */
    private function validateNumberString(array $number, array $numerals): bool
    {
        # Get difference of number against its numerals and returns each unique element.
        $result = array_udiff($number, $numerals, "strcasecmp");

        # If the result is greater than 0 elements.
        if (count($result) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if a number is negative, false if not.
     *
     * @param array $number
     * @param string $negative
     * @return bool
     */
    private function isNegativeArray(array $number, string $negative)
    {
        if ($number[0] !== $negative) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if a number is negative, false if not.
     *
     * @param string $number
     * @param string $negative
     * @return bool
     */
    private function isNegativeString(string $number, string $negative)
    {
        if (strpos($number, $negative) === false) {
            return false;
        }

        return true;
    }
    // </editor-fold>

    // <editor-fold desc="<BCMath Extensions>" defaultstate="collapsed">
    /**
     * Round full up BC math numbers (ceiling).
     *
     * @param string $number
     * @param int $resolution
     * @return string
     * @author Alix Axel <https://stackoverflow.com/users/89771/alix-axel>
     */
    protected function bcceil($number, int $resolution = 0): string
    {
        if ($number[0] != '-') {
            return bcadd($number, 1, $resolution);
        }

        return bcsub($number, 0, $resolution);
    }

    /**
     * Round full down BC math numbers (floor).
     *
     * @param string $number
     * @param int $resolution
     * @return string
     * @author Alix Axel <https://stackoverflow.com/users/89771/alix-axel>
     */
    protected function bcfloor(string $number, int $resolution = 0): string
    {
        if ($number[0] != '-') {
            return bcadd($number, 0, $resolution);
        }

        return bcsub($number, 1, $resolution);
    }

    /**
     * Round BC math numbers to a precision.
     *
     * @param $number
     * @param int $resolution
     * @return string
     * @author Alix Axel <https://stackoverflow.com/users/89771/alix-axel>
     */
    protected function bcround(string $number, int $resolution = 0): string
    {
        if ($number[0] != '-') {
            return bcadd($number, '0.' . str_repeat('0', $resolution) . '5', $resolution);
        }

        return bcsub($number, '0.' . str_repeat('0', $resolution) . '5', $resolution);
    }

    /**
     * Truncate a BC math number (truncation).
     *
     * Requires BCMath scale 0.
     *
     * @param string $number
     * @param int $resolution
     * @return string
     */
    protected function bctruncate(string $number, int $resolution = 0): string
    {
        $int = bcdiv($number, 1, 0);

        return bcsub($number, $int, $resolution);
    }
    // </editor-fold>

    // <editor-fold desc="<Get/Set>" defaultstate="collapsed">
    /**
     * Returns the use arbitrary precision flag.
     *
     * @return bool
     */
    public function getUseArbitraryPrecision(): bool
    {
        return $this->useArbitraryPrecision;
    }

    /**
     * Sets the use arbitrary precision flag.
     *
     * Flagging this as true will use BCMath for arbitrarily large numbers.
     *
     * Do not flag this as true if BCMath is not installed.
     *
     * @param bool $arbitraryPrecision
     */
    public function setUseArbitraryPrecision (bool $arbitraryPrecision)
    {
        $this->useArbitraryPrecision = $arbitraryPrecision;
    }

    /**
     * Returns the max number length.
     *
     * @return int
     */
    public function getMaxNumberLength(): int
    {
        return $this->maxNumberLength;
    }

    /**
     * Sets max number length.
     *
     * @param int $maxNumberLength
     */
    public function setMaxNumberLength (int $maxNumberLength)
    {
        $this->maxNumberLength = $maxNumberLength;
    }

    /**
     * Returns Base A's Name.
     *
     * @return string
     */
    public function getBaseAName(): string
    {
        return $this->baseAName;
    }

    /**
     * Sets Base A's Name.
     *
     * @param string $name
     */
    public function setBaseAName ($name)
    {
        $this->baseAName = $name;
    }

    /**
     * Returns Base A Radix.
     *
     * @return int
     */
    public function getBaseARadix(): int
    {
        return $this->baseARadix;
    }

    /**
     * Sets base A Radix.
     */
    protected function setBaseARadix()
    {
        $this->baseARadix = count($this->getBaseANumerals());
    }

    /**
     * Returns Base A Numerals.
     *
     * @return array
     */
    public function getBaseANumerals(): array
    {
        return $this->baseANumerals;
    }

    /**
     * Sets Base A Numerals.
     *
     * @param array $numerals
     * @throws RuntimeException
     */
    public function setBaseANumerals(array $numerals)
    {
        try {
            if (count($numerals) !== count(array_flip($numerals))) {
                throw new OutOfBoundsException(self::E_NUMERALS_LIST_NOT_UNIQUE);
            }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_SET_BASE_A_NUMERALS,
                    $this->getBaseAName(),
                    $re->getMessage()
                )
            );
        }

        $this->baseANumerals = $numerals;
    }

    /**
     * Returns Base B's Name.
     *
     * @return string
     */
    public function getBaseBName(): string
    {
        return $this->baseBName;
    }

    /**
     * Sets Base B's Name.
     *
     * @param string $name
     */
    public function setBaseBName ($name)
    {
        $this->baseBName = $name;
    }

    /**
     * Returns Base B Radix.
     *
     * @return int
     */
    public function getBaseBRadix(): int
    {
        return $this->baseBRadix;
    }

    /**
     * Sets Base B Radix.
     */
    protected function setBaseBRadix()
    {
        $this->baseBRadix = count($this->getBaseBNumerals());
    }

    /**
     * Returns Base B Numerals.
     *
     * @return array
     */
    public function getBaseBNumerals(): array
    {
        return $this->baseBNumerals;
    }

    /**
     * Sets Base B Numerals.
     *
     * @param array $numerals
     * @throws RuntimeException
     */
    public function setBaseBNumerals(array $numerals)
    {
        try {
            if (count($numerals) !== count(array_flip($numerals))) {
                throw new OutOfBoundsException(self::E_NUMERALS_LIST_NOT_UNIQUE);
            }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_SET_BASE_B_NUMERALS,
                    $this->getBaseBName(),
                    $re->getMessage()
                )
            );
        }

        $this->baseBNumerals = $numerals;
    }

    /**
     * Returns Base A's orders separator.
     *
     * @return string
     */
    public function getBaseAOrdersSeperator(): string
    {
        return $this->baseAOrdersSeperator;
    }

    /**
     * Sets Base A's orders separator.
     *
     * @param string $separator
     */
    public function setBaseAOrdersSeperator(string $separator)
    {
        $this->baseAOrdersSeperator = $separator;
    }

    /**
     * Returns Base A's fractional delimiter.
     *
     * @return string
     */
    public function getBaseAFractionalDelimiter(): string
    {
        return $this->baseAFractionalDelimiter;
    }

    /**
     * Sets Base A's fractional delimiter.
     *
     * @param string $delimiter
     */
    public function setBaseAFractionalDelimiter(string $delimiter)
    {
        $this->baseAFractionalDelimiter = $delimiter;
    }

    /**
     * Returns Base A's negative sign.
     *
     * @return string
     */
    public function getBaseANegativeSign(): string
    {
        return $this->baseANegativeSign;
    }

    /**
     * Sets Base A's negative sign.
     *
     * @param string $sign
     */
    public function setBaseANegativeSign(string $sign)
    {
        $this->baseANegativeSign = $sign;
    }

    /**
     * Returns Base A's orders separator.
     *
     * @return int
     */
    public function getBaseAOrdersCount(): int
    {
        return $this->baseAOrdersCount;
    }

    /**
     * Sets Base A's orders separator.
     *
     * @param int $count
     */
    public function setBaseAOrdersCount(int $count)
    {
        $this->baseAOrdersCount = $count;
    }

    /**
     * Returns Base B's orders separator.
     *
     * @return string
     */
    public function getBaseBOrdersSeperator(): string
    {
        return $this->baseBOrdersSeperator;
    }

    /**
     * Sets Base B's orders separator.
     *
     * @param string $separator
     */
    public function setBaseBOrdersSeperator(string $separator)
    {
        $this->baseBOrdersSeperator = $separator;
    }

    /**
     * Returns Base B's fractional delimiter.
     *
     * @return string
     */
    public function getBaseBFractionalDelimiter(): string
    {
        return $this->baseBFractionalDelimiter;
    }

    /**
     * Sets Base B's fractional delimiter.
     *
     * @param string $delimiter
     */
    public function setBaseBFractionalDelimiter(string $delimiter)
    {
        $this->baseBFractionalDelimiter = $delimiter;
    }

    /**
     * Returns Base B's negative sign.
     *
     * @return string
     */
    public function getBaseBNegativeSign(): string
    {
        return $this->baseBNegativeSign;
    }

    /**
     * Sets Base B's negative sign.
     *
     * @param string $sign
     */
    public function setBaseBNegativeSign(string $sign)
    {
        $this->baseBNegativeSign = $sign;
    }

    /**
     * Returns Base B's orders separator.
     *
     * @return int
     */
    public function getBaseBOrdersCount(): int
    {
        return $this->baseBOrdersCount;
    }

    /**
     * Sets Base B's orders separator.
     *
     * @param int $count
     */
    public function setBaseBOrdersCount(int $count)
    {
        $this->baseBOrdersCount = $count;
    }
    // </editor-fold>
}
