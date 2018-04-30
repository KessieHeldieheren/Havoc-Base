<?php

/**
 * Base conversions class.
 *
 * Commonly called a Base N Converter (Base N).
 *
 * Allows converting between arbitrary bases in positional numeral systems, given arbitrary numerals.
 *
 * This is intended to work with CodeIgniter 3 as a library,
 * so the constructor takes an array of parameters.
 *
 * Provide one parameter with a key named base_a_numerals, and an auto-indexed array of the numerals.
 * Do the same for an array key named base_b_numerals.
 *
 * $params = [
 *  base_a_numerals [ "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ],
 *  base_b_numerals [ "X", "E", "D", "T", "N", "F", "H", "K", "V", "L", "A", "Q" ],
 *  base_a_orderscount [ 3 ],
 *  base_b_orderscount [ 3 ],
 *  base_a_ordersseparator [ "," ],
 *  base_b_ordersseparator [ " " ],
 *  base_a_fractionaldelimiter [ "." ],
 *  base_b_fractionaldelimiter [ ";" ]
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
 *
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
 * @todo Implement arbitrary numeric precision (BCMath or GMP) to exceed integer limits. This is a priority (v2.5).
 *
 * @author Kessie Heldieheren <me@kessie.gold>
 * @package Havoc
 * @version 2.45
 */
class Havoc_Base
{
	// <editor-fold desc="<System Messages>" defaultstate="collapsed">
	/* Class messages (titles) */
	const EF_CANNOT_INSTANTIATE_CLASS = "Cannot instantiate HavocBase because %s.";
	const EF_CANNOT_CONVERT_FROM_BASE = "Cannot convert anything from a base because %s.";
	const EF_CANNOT_SET_BASE_A_NUMERALS = "Cannot set %s's numerals list because %s.";
	const EF_CANNOT_SET_BASE_B_NUMERALS = "Cannot set %s's numerals list because %s.";
	const EF_CANNOT_CONVERT_AB = "Cannot begin a conversion from %s to %s because %s.";
	const EF_CANNOT_CONVERT_BA = "Cannot begin a conversion from %s to %s because %s.";
	const EF_CANNOT_INTDIV = "Cannot perform division on the input because %s.";
	const EF_CANNOT_FORMAT_A = "Cannot render %s number formatted because %s.";
	const EF_CANNOT_FORMAT_B = "Cannot render %s number formatted because %s.";

	/* Class messages (messages) */
	const E_BASE_A_NUMERALS_EMPTY = "the first parameter is empty or not an array";
	const E_DIGITS_ARRAY_EMPTY = "the array of digits is empty";
	const E_NUMERALS_LIST_NOT_UNIQUE = "the numerals list contains duplicate symbols";
	const E_NO_NUMBER = "no number was provided";
	const E_NUMERALS_INVALID = "the number provided is not using the correct numerals";
	const E_INPUT_EXCEEDS_INT_LIMIT = "the number provided is too long";
	// </editor-fold>

	// <editor-fold desc="<Properties>" defaultstate="collapsed">
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
	}
	// </editor-fold>

	// <editor-fold desc="<Main Conversion Methods>" defaultstate="collapsed">
	/**
	 * Converts a number in base A into base B, using Base B's numerals instead of an integer.
	 *
	 * Method also takes fractions, formatted as prescribed. This method also has provisions for input given
	 * using the number format provided.
	 *
	 * @param string $number
	 * @param bool $convert_to_base_numerals
	 * @param bool $return_array
	 * @param bool $format_numeric
	 * @return array|string
	 */
	public function convertAb(
		string $number,
		bool $convert_to_base_numerals = true,
		bool $return_array = true,
		bool $format_numeric = false
	) {
		# These variables assist in number validation.
		# Fractional delimiter of host base.
		$hostDelimiter = $this->getBaseAFractionalDelimiter();

		# Fractional delimiter of target base.
		$targetDelimiter = $this->getBaseBFractionalDelimiter();

		# Orders separator of host base.
		$hostOrdersSeperator = $this->getBaseAOrdersSeperator();

		# Radix of host base.
		$hostBase = $this->getBaseARadix();

		# Radix of target base.
		$targetBase = $this->getBaseBRadix();

		# Radix, delimiter, and separator to provide a list of valid input.
		$validNumerals = array_merge($this->getBaseANumerals(), [$hostDelimiter, $hostOrdersSeperator]);

		# The entire number split into an array.
		$numberAsArray = str_split($number);

		try {
			# If an empty string is given as an input. This is not a valid number.
			if ("" === $number) {
				throw new OutOfBoundsException(self::E_NO_NUMBER);
			}

			# If an input is not a valid host base number (including formatting).
			if (false === $this->validateNumberString($numberAsArray, $validNumerals)) {
				throw new OutOfBoundsException(self::E_NUMERALS_INVALID);
			}
		} catch (RuntimeException $re) {
			throw new RuntimeException(
				sprintf(
					self::EF_CANNOT_CONVERT_AB,
					$this->getBaseAName(),
					$this->getBaseBName(),
					$re->getMessage()
				)
			);
		}

		# Split input into its components:-
		# [0] = whole number component.
		# [1] = fraction component if provided.
		$components = $this->splitNumberIntoComponents($number, $hostDelimiter);

		# If a fractional element was given as input.
		if (isset($components[1])) {
			# Fractional element split into an array.
			$fraction = str_split($components[1]);

			# Fractional element rendered as indices of its radix.
			$fraction_digits = $this->renderNumeralsInBaseAIndices($fraction);

			# Fractional precision (a.k.a decimal precision) of the fractional element.
			$resolution = count($fraction_digits);

			# Fractional element converted from its base as indices into a decimal fraction.
			$baseAToDec = $this->convertFractionBaseToDec($fraction_digits, $hostBase);

			# Decimal of previous result converted into the target base as indices of its radix.
			$result_fraction = $this->renderDigitsInBaseBNumerals(
				$this->convertFractionDecToBase($baseAToDec, $resolution, $targetBase)
			);
		}

		# The basic number, stripped of the orders separators.
		$number = str_replace($hostOrdersSeperator, "", $components[0]);

		# Number element (integer) element split into an array.
		$number = str_split($number, 1);

		# Number element rendered as indices of its radix.
		$number_digits = $this->renderNumeralsInBaseAIndices($number);

		# Number element converted into the target base as indices of its radix.
		$result_number = $this->convertArrayAb(
			$number_digits,
			$convert_to_base_numerals,
			$return_array,
			$format_numeric
		);

		# If a fractional element was set.
		if (isset($result_fraction)) {
			# If not returning an array result
			if (false === $return_array) {
				# Format fractional element as a string.
				$result_fraction = implode("", $result_fraction);

				# Return number and fractional element joined by the target base delimiter.
				return ($result_number . $targetDelimiter . $result_fraction);
			}

			# Return an array, merging the number and fractional element joined by the target base delimiter.
			return (array_merge($result_number, $targetDelimiter, $result_fraction));
		}

		# Return an array containing only the number element.
		return $result_number;
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
	 * @param string $number
	 * @param bool $convert_to_base_numerals
	 * @param bool $return_array
	 * @param bool $format_numeric
	 * @return array|string
	 */
	public function convertBa(
		string $number,
		bool $convert_to_base_numerals = true,
		bool $return_array = true,
		bool $format_numeric = false
	) {
		# These variables assist in number validation.
		# Fractional delimiter of host base.
		$hostDelimiter = $this->getBaseBFractionalDelimiter();

		# Fractional delimiter of target base.
		$targetDelimiter = $this->getBaseAFractionalDelimiter();

		# Orders separator of host base.
		$hostOrdersSeperator = $this->getBaseBOrdersSeperator();

		# Radix of host base.
		$hostBase = $this->getBaseBRadix();

		# Radix of target base.
		$targetBase = $this->getBaseARadix();

		# Radix, delimiter, and separator to provide a list of valid input.
		$validNumerals = array_merge($this->getBaseBNumerals(), [$hostDelimiter, $hostOrdersSeperator]);

		# The entire number split into an array.
		$numberAsArray = str_split($number);

		try {
			# If an empty string is given as an input. This is not a valid number.
			if ("" === $number) {
				throw new OutOfBoundsException(self::E_NO_NUMBER);
			}

			# If an input is not a valid host base number (including formatting).
			if (false === $this->validateNumberString($numberAsArray, $validNumerals)) {
				throw new OutOfBoundsException(self::E_NUMERALS_INVALID);
			}
		} catch (RuntimeException $re) {
			throw new RuntimeException(
				sprintf(
					self::EF_CANNOT_CONVERT_AB,
					$this->getBaseBName(),
					$this->getBaseAName(),
					$re->getMessage()
				)
			);
		}

		# Split input into its components:-
		# [0] = whole number component.
		# [1] = fraction component if provided.
		$components = $this->splitNumberIntoComponents($number, $hostDelimiter);

		# If a fractional element was given as input.
		if (isset($components[1])) {
			# Fractional element split into an array.
			$fraction = str_split($components[1]);

			# Fractional element rendered as indices of its radix.
			$fraction_digits = $this->renderNumeralsInBaseBIndices($fraction);

			# Fractional precision (a.k.a decimal precision) of the fractional element.
			$resolution = count($fraction_digits);

			# Fractional element converted from its base as indices into a decimal fraction.
			$baseAToDec = $this->convertFractionBaseToDec($fraction_digits, $hostBase);

			# Decimal of previous result converted into the target base as indices of its radix.
			$result_fraction = $this->renderDigitsInBaseANumerals(
				$this->convertFractionDecToBase($baseAToDec, $resolution, $targetBase)
			);
		}

		# The basic number, stripped of the orders separators.
		$number = str_replace($hostOrdersSeperator, "", $components[0]);

		# Number element (integer) element split into an array.
		$number = str_split($number, 1);

		# Number element rendered as indices of its radix.
		$number_digits = $this->renderNumeralsInBaseBIndices($number);

		# Number element converted into the target base as indices of its radix.
		$result_number = $this->convertArrayBa(
			$number_digits,
			$convert_to_base_numerals,
			$return_array,
			$format_numeric
		);

		# If a fractional element was set.
		if (isset($result_fraction)) {
			# If not returning an array result.
			if (false === $return_array) {
				# Format fractional element as a string.
				$result_fraction = implode("", $result_fraction);

				# Return number and fractional element joined by the target base delimiter.
				return ($result_number . $targetDelimiter . $result_fraction);
			}

			# Return an array, merging the number and fractional element joined by the target base delimiter.
			return (array_merge($result_number, $targetDelimiter, $result_fraction));
		}

		# Return an array containing only the number element.
		return $result_number;
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
	// </editor-fold>

	// <editor-fold desc="<Formatting Numbers>" defaultstate="collapsed">
	/**
	 * Returns a raw Base A number numerically formatted.
	 *
	 * @param string $number
	 * @param bool $return_array
	 * @return array|string
	 */
	public function formatBaseANumber(string $number, bool $return_array = false)
	{
		# These variables assist in number validation.
		# Fractional delimiter of host base.
		$hostDelimiter = $this->getBaseAFractionalDelimiter();

		# Orders separator of host base.
		$hostOrdersSeperator = $this->getBaseAOrdersSeperator();

		# Radix, delimiter, and separator to provide a list of valid input.
		$validNumerals = array_merge($this->getBaseANumerals(), [$hostDelimiter, $hostOrdersSeperator]);

		# The entire number split into an array.
		$numberAsArray = str_split($number);

		try {
			# If an empty string is given as an input. This is not a valid number.
			if ("" === $number) {
				throw new OutOfBoundsException(self::E_NO_NUMBER);
			}

			# If an input is not a valid host base number (including formatting).
			if (false === $this->validateNumberString($numberAsArray, $validNumerals)) {
				throw new OutOfBoundsException(self::E_NUMERALS_INVALID);
			}
		} catch (RuntimeException $re) {
			throw new RuntimeException(
				sprintf(
					self::EF_CANNOT_FORMAT_A,
					$this->getBaseAName(),
					$re->getMessage()
				)
			);
		}

		# Split input into its components:-
		# [0] = whole number component.
		# [1] = fraction component if provided.
		$components = $this->splitNumberIntoComponents($number, $hostDelimiter);

		# If a fractional element was given as input.
		if (isset($components[1])) {
			$fraction = str_split($components[1]);
			$fraction_digits = $this->renderNumeralsInBaseAIndices($fraction);
			$result_fraction = $this->renderDigitsInBaseANumerals($fraction_digits);
		}

		# The basic number, stripped of the orders separators.
		$number = str_replace($hostOrdersSeperator, "", $components[0]);

		# Number element (integer) element split into an array.
		$number = str_split($number, 1);

		# Number element rendered as indices of its radix.
		$number_digits = $this->renderNumeralsInBaseAIndices($number);

		# Number element formatted.
		$result_number = $this->formatBaseANumeric($this->renderDigitsInBaseANumerals($number_digits));

		# If returning an array.
		if ($return_array) {
			# If the fractional element is set.
			if (isset($result_fraction)) {
				# Return an array, merging the number and fractional element joined by the target base delimiter.
				return (array_merge($result_number, [$hostDelimiter], $result_fraction));
			}

			# Return an array containing only the number element.
			return ($result_number);
		}

		# If the fractional element is set.
		if (isset($result_fraction)) {
			# Return the number and fractional element as a string.
			return implode("", array_merge($result_number, [$hostDelimiter], $result_fraction));
		}

		# Return the number element as a string.
		return (implode("", $result_number));
	}

	/**
	 * Returns a raw Base B number numerically formatted.
	 *
	 * @param string $number
	 * @param bool $return_array
	 * @return array|string
	 */
	public function formatBaseBNumber(string $number, bool $return_array = false)
	{
		# These variables assist in number validation.
		# Fractional delimiter of host base.
		$hostDelimiter = $this->getBaseBFractionalDelimiter();

		# Orders separator of host base.
		$hostOrdersSeperator = $this->getBaseBOrdersSeperator();

		# Radix, delimiter, and separator to provide a list of valid input.
		$validNumerals = array_merge($this->getBaseBNumerals(), [$hostDelimiter, $hostOrdersSeperator]);

		# The entire number split into an array.
		$numberAsArray = str_split($number);

		try {
			# If an empty string is given as an input. This is not a valid number.
			if ("" === $number) {
				throw new OutOfBoundsException(self::E_NO_NUMBER);
			}

			# If an input is not a valid host base number (including formatting).
			if (false === $this->validateNumberString($numberAsArray, $validNumerals)) {
				throw new OutOfBoundsException(self::E_NUMERALS_INVALID);
			}
		} catch (RuntimeException $re) {
			throw new RuntimeException(
				sprintf(
					self::EF_CANNOT_FORMAT_B,
					$this->getBaseBName(),
					$re->getMessage()
				)
			);
		}

		# Split input into its components:-
		# [0] = whole number component.
		# [1] = fraction component if provided.
		$components = $this->splitNumberIntoComponents($number, $hostDelimiter);

		# If a fractional element was given as input.
		if (isset($components[1])) {
			$fraction = str_split($components[1]);
			$fraction_digits = $this->renderNumeralsInBaseBIndices($fraction);
			$result_fraction = $this->renderDigitsInBaseBNumerals($fraction_digits);
		}

		# The basic number, stripped of the orders separators.
		$number = str_replace($hostOrdersSeperator, "", $components[0]);

		# Number element (integer) element split into an array.
		$number = str_split($number, 1);

		# Number element rendered as indices of its radix.
		$number_digits = $this->renderNumeralsInBaseBIndices($number);

		# Number element formatted.
		$result_number = $this->formatBaseBNumeric($this->renderDigitsInBaseBNumerals($number_digits));

		# If returning an array.
		if ($return_array) {
			# If the fractional element is set.
			if (isset($result_fraction)) {
				# Return an array, merging the number and fractional element joined by the target base delimiter.
				return (array_merge($result_number, [$hostDelimiter], $result_fraction));
			}

			# Return an array containing only the number element.
			return ($result_number);
		}

		# If the fractional element is set.
		if (isset($result_fraction)) {
			# Return the number and fractional element as a string.
			return implode("", array_merge($result_number, [$hostDelimiter], $result_fraction));
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
	private function convertFractionBaseToDec(array $fraction = [], $base): float
	{
		# Prevents a rounding error.
		array_push($fraction, 0);

		# Resolution of the fraction (a.k.a decimal precision).
		$resolution = count($fraction);

		# Iteration pointer. This is part of the equation below and power'd.
		# For ever place we move we decrement this.
		$iteration = -1;

		# Equation pointer. Stores our value as we iterate every place.
		$pointer = 0;

		# Loop over the fraction as digits.
		foreach ($fraction as $digit) {
			# (pointer + digit * base ^ iteration)
			$pointer = $pointer + $digit * $base ** $iteration;

			# Decrement iteration value.
			$iteration--;
		}

		# Return a result rounded to the nearest decimal place.
		return round($pointer, $resolution);
	}

	/**
	 * Convert a fraction in decimal to Base A.
	 *
	 * This is the final part of fraction conversions.
	 *
	 * @param $fraction
	 * @param int $resolution
	 * @param int $base
	 * @return array
	 */
	private function convertFractionDecToBase($fraction, $resolution = 1, $base): array
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

				# TODO This should be looked at.
				# Converting 0.33 to dozenal yields a 2nd decimal place index of [12] when it rounds up.
				# This clamps any decimal places to within the bounds of their respective radix.
				# Such as in the example above, where [12] is clamped to [11]. [12] is not an index in base 12.
				if ($resolved >= $base) {
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
			# TODO Not having to suppress this error would be nice.
			# Triggers a warning when giving a float because PHP converts huge ints to floats.
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
	 * Convert a number in base A into base B.
	 *
	 * @param array $indices
	 * @return array
	 */
	private function convertBaseAb(array $indices): array
	{
		# Radix of host base.
		$hostBase = $this->getBaseARadix();

		# Radix of target base.
		$targetBase = $this->getBaseBRadix();

		# Digits converted from the host base.
		$converted = $this->convertFromBase($indices, $hostBase);

		# Return digits converted into the target base.
		return(
			$this->convertToBase(
				$converted,
				$targetBase
			)
		);
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
		$hostBase = $this->getBaseBRadix();

		# Radix of target base.
		$targetBase = $this->getBaseARadix();

		# Digits converted from the host base.
		$converted = $this->convertFromBase($indices, $hostBase);

		# Return digits converted into the target base.
		return(
			$this->convertToBase(
				$converted,
				$targetBase
			)
		);
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
		$numerals = $this->getBaseANumerals();

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
	 * @param array $digits
	 * @param string $orders_separator
	 * @param int $orders_count
	 * @return array
	 */
	private function formatBaseXNumeric(array $digits, string $orders_separator, int $orders_count): array
	{
		# Split the array into chunks, where the length of chunks is defined by the orders separator.
		# Then we reverse the array so that we can iterate over it and add the orders separator as we go.
		# If you do not reverse the array, you won't get accurate results for non-multiples of the order.
		$digits = array_chunk(array_reverse($digits), $orders_count);

		# Result.
		$result = [];

		# For each digit as a set.
		foreach ($digits as $set) {
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
	private function validateNumberString($number, $numerals): bool
	{
		# Get difference of number against its numerals and returns each unique element.
		$result = array_udiff($number, $numerals, "strcasecmp");

		# If the result is greater than 0 elements.
		if (count($result) > 0) {
			return false;
		}

		return true;
	}
	// </editor-fold>

	// <editor-fold desc="<Get/Set>" defaultstate="collapsed">
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
