<?php
$validation_error_messages = array(
	'REQUIRED_ERROR' => Label::getLabel('LBL_{caption}_IS_MANDATORY'),
	'CHARONLY_ERROR' =>  Label::getLabel('LBL_ONLY_CHARACTERS_ARE_SUPPORTED_FOR_{caption}'),
	'INT_ERROR' => Label::getLabel('LBL_PLEASE_ENTER_INTEGER_VALUE_FOR_{caption}'),
	'FLOAT_ERROR' => Label::getLabel('LBL_PLEASE_ENTER_NUMERIC_VALUE_FOR_{caption}'),
	'LENGTHRANGE_ERROR' => Label::getLabel('LBL_LENGTH_OF_{caption}_MUST_BE_BETWEEN_{minlength}_AND_{maxlength}'),
	'SELECTIONRANGE_ERROR' => Label::getLabel('LBL_PLEASE_SELECT_{minselectionsize}_TO_{maxselectionsize}_OPTIONS_FOR_{caption}'),
	'RANGE_ERROR' => Label::getLabel('LBL_VALUE_OF_{caption}_MUST_BE_BETWEEN_{minval}_AND_{maxval}'),
	'USERNAME_ERROR' => Label::getLabel('LBL_USERNAME_ERROR'),
	'PASSWORD_ERROR' => Label::getLabel('LBL_{caption}_SHOULD_BE_6_TO_20_CHARACTERS_LONG.'),
	'COMPAREWITH_LT_ERROR' => Label::getLabel('LBL_{caption}_MUST_BE_LESS_THAN_{comparefield}'),
	'COMPAREWITH_LE_ERROR' => Label::getLabel('LBL_{caption}_MUST_BE_LESS_THAN_OR_EQUAL_TO_{comparefield}'),
	'COMPAREWITH_GT_ERROR' => Label::getLabel('LBL_{caption}_MUST_BE_GREATOR_THAN_{comparefield}'),
	'COMPAREWITH_GE_ERROR' => Label::getLabel('LBL_{caption}_MUST_BE_GREATOR_THAN_OR_EQUAL_TO_{comparefield}'),
	'COMPAREWITH_EQ_ERROR' => Label::getLabel('LBL_{caption}_MUST_BE_SAME_AS_{comparefield}'),
	'COMPAREWITH_NE_ERROR' => Label::getLabel('LBL_{caption}_SHOULD_NOT_BE_SAME_AS_{comparefield}'),
	'EMAIL_ERROR' => Label::getLabel('LBL_PLEASE_ENTER_VALID_EMAIL_ID_FOR_{caption}'),
	'USER_REGEX_ERROR' => Label::getLabel('LBL_INVALID_VALUE_FOR_{caption}'),
);
?>
