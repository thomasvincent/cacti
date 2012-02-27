<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2012 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/* default localization of Cacti */
$cacti_locale = 'en';
$cacti_country = 'us';

/* an array that will contains all textdomains being in use. */
$cacti_textdomains = array();

/* use a fallback if i18n is disabled (default) */
if (! read_config_option('i18n_language_support') && read_config_option('i18n_language_support') != '')
{
	load_fallback_procedure();
	return;
}

/* get a list of locale settings */
$lang2locale = get_list_of_locales();

/* determine whether or not we can support the language */
if (isset($_GET['language']) && isset($lang2locale[$_GET['language']]))
/* user requests another language */
{
	$cacti_locale = $_GET['language'];
	$cacti_country = $lang2locale[$_GET['language']]['country'];
	$_SESSION['sess_i18n_language'] = $cacti_locale;
	unset($_SESSION['sess_current_date1']);
	unset($_SESSION['sess_current_date2']);

	/* save customized language setting (authenticated users only) */
	set_user_config_option('language', $cacti_locale);

}
/* language definition stored in the SESSION */
elseif (isset($_SESSION['sess_i18n_language']) && isset($lang2locale[$_SESSION['sess_i18n_language']]))
{
	$cacti_locale = $_SESSION['sess_i18n_language'];
	$cacti_country = $lang2locale[$_SESSION['sess_i18n_language']]['country'];

}
elseif ($user_locale = read_user_config_option('language'))
/* look up for user customized language setting stored in Cacti DB */
{
	if (isset($lang2locale[$user_locale]))
	{
		$cacti_locale = $user_locale;
		$cacti_country = $lang2locale[$cacti_locale]['country'];
		$_SESSION['sess_i18n_language'] = $cacti_locale;
	}

}
elseif ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && ( read_config_option('i18n_auto_detection') | read_config_option('i18n_auto_detection') == '' ) )
/* detect browser settings if auto detection is enabled */
{
	$accepted = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	$accepted = strtolower(str_replace(strstr($accepted, ','), '', $accepted));

	$accepted = (isset($lang2locale[$accepted])) ? $accepted : str_replace(strstr($accepted, '-'), '', $accepted);

	if (isset($lang2locale[$accepted]))
	{
		$cacti_locale = $accepted;
		$cacti_country = $lang2locale[$accepted]['country'];
	}

}
else
/* use the default language defined under 'general' */
{
	$accepted = read_config_option('i18n_default_language');
	if ($accepted == '')
	{
		$accepted = read_default_config_option('i18n_default_language');
	}

	if (isset($lang2locale[$accepted]))
	{
		$cacti_locale = $accepted;
		$cacti_country = $lang2locale[$accepted]['country'];
	}
}

/* define the path to the language file */
$path2catalogue = CACTI_BASE_PATH . '/locales/LC_MESSAGES/' . $lang2locale[$cacti_locale]['filename'] . '.mo';

/* define the path to the language file of the DHTML calendar */
$path2calendar = CACTI_BASE_PATH . '/include/js/jquery/locales/LC_MESSAGES/jquery.ui.datepicker-' . $lang2locale[$cacti_locale]['filename'] . '.js';

/* use fallback procedure if requested language is not available */
if (file_exists($path2catalogue) & file_exists($path2calendar))
{
	$cacti_textdomains['cacti']['path2locales'] = CACTI_BASE_PATH . '/locales';
	$cacti_textdomains['cacti']['path2catalogue'] = $path2catalogue;
}
else
{
	load_fallback_procedure();
	return;
}

/* search the correct textdomains for all plugins being installed */
$plugins = db_fetch_assoc('SELECT `directory` FROM `plugin_config` ORDER BY sequence ASC');
if ($plugins && sizeof($plugins) > 0)
{
	foreach($plugins as $plugin)
	{
		$plugin = $plugin['directory'];
		$path2catalogue =  CACTI_BASE_PATH . '/plugins/' . $plugin . '/locales/LC_MESSAGES/' . $lang2locale[$cacti_locale]['filename'] . '.mo';

		if (file_exists($path2catalogue))
		{
			$cacti_textdomains[$plugin]['path2locales'] = CACTI_BASE_PATH . '/plugins/' . $plugin . '/locales';
			$cacti_textdomains[$plugin]['path2catalogue'] = $path2catalogue;
		}
	}

	/* if i18n support is set to strict mode then check if all plugins support the requested language */
	if (read_config_option('i18n_language_support') == 2)
	{
		if(sizeof($plugins) != (sizeof($cacti_textdomains) - 1))
		{
			load_fallback_procedure();
			return;
		}
	}
}

/* load php-gettext class */
require(CACTI_BASE_PATH . '/include/gettext/streams.php');
require(CACTI_BASE_PATH . '/include/gettext/gettext.php');

/* prefetch all language files to work in memory only,
   die if one of the language files is corrupted */
$l10n = array();

foreach($cacti_textdomains as $domain => $paths) {
	$input = new FileReader($cacti_textdomains[$domain]['path2catalogue']);
	if($input == false) {
		die('Unable to read file: ' . $cacti_textdomains[$domain]['path2catalogue']);
	}

	$l10n[$domain] = new gettext_reader($input);
	if($l10n[$domain] == false) {
		die('Invalid language file: ' . $cacti_textdomains[$domain]['path2catalogue']);
	}
}

/* load standard wrappers */
load_i18n_gettext_wrappers();

define('CACTI_LOCALE', $cacti_locale);
define('CACTI_COUNTRY', $cacti_country);
define('CACTI_LANGUAGE', $lang2locale[CACTI_LOCALE]['language']);
define('CACTI_LANGUAGE_FILE', $lang2locale[CACTI_LOCALE]['filename']);


/**
 * load_fallback_procedure - loads wrapper package if native language (English) has to be used
 *
 * @return
 */
function load_fallback_procedure(){
	global $cacti_textdomains, $cacti_locale, $cacti_country, $lang2locale;

	/* load wrappers if native gettext is not available */
	load_i18n_fallback_wrappers();

	/* reset variables */
	$_SESSION['sess_i18n_language'] = '';

	$cacti_textdomains = array();
	define('CACTI_LOCALE', 'en');
	define('CACTI_COUNTRY', 'us');
	define('CACTI_LANGUAGE', 'English');
	define('CACTI_LANGUAGE_FILE', 'english_usa');
}



/**
 * load_i18n_gettext_wrappers - creates all wrappers to translate strings by using php-gettext
 *
 * @return
 */
function load_i18n_gettext_wrappers(){

	function __gettext($text, $domain = 'cacti') {
		global $l10n;
		if (isset($l10n[$domain])) {
			return $l10n[$domain]->translate($text);
		}else {
			return $text;
		}

	}


	function __n($single, $plural, $number, $domain = 'cacti') {
		global $l10n;
		return $l10n->_ngettext($single, $plural, $number);
	}


	function __() {
		global $l10n;

		$args = func_get_args();
		$num  = func_num_args();

		/* this should not happen */
		if ($num < 1) {
			return false;

		/* convert pure text strings */
		}elseif ($num == 1) {
			return __gettext($args[0]);

		/* convert pure text strings by using a different textdomain */
		}elseif ($num == 2 && isset($l10n[$args[1]])) {
			return __gettext($args[0], $args[1]);

		/* convert stings including one or more placeholders */
		}else {

			/* only the last argument is allowed to initiate
			the use of a different textdomain */

			/* get gettext string */
			$args[0] = isset($l10n[$args[$num-1]]) 	? __gettext($args[0], $args[$num-1])
													: __gettext($args[0]);

			/* process return string against input arguments */
			return call_user_func_array('sprintf', $args);
		}
	}


	function __date($format, $timestamp = false, $domain = 'cacti') {

		global $i18n_date_placeholders;

		if (!$timestamp) {
			$timestamp = time();
		}

		/* placeholders will allow to fill in the translated weekdays, month and so on.. */
		$i18n_date_placeholders = array(
			'#1' => __(date('D', $timestamp), $domain),
			'#2' => str_replace('_', '', __( '_'  . date('M', $timestamp) . '_', $domain)),
			'#3' => str_replace('_', '', __( '__' . date('F', $timestamp) . '_', $domain)),
			'#4' => __(date('l', $timestamp), $domain)
		);

		/* if defined exchange the format string for the configured locale */
		$format = __gettext($format, $domain);

		/* replace special date chars by placeholders */
		$format = str_replace(array('D', 'M', 'F', 'l'), array('#1', '#2', '#3', '#4'), $format);

		/* get date string included placeholders */
		$date = date($format, $timestamp);

		/* fill in specific translations */
		$date = str_replace(array_keys($i18n_date_placeholders), array_values($i18n_date_placeholders), $date);

		return $date;
	}

}



/**
 * load_i18n_fallback_wrappers - creates special wrappers to leave the native language untouched
 *
 * @return
 */
function load_i18n_fallback_wrappers(){

	function __gettext($text, $domain = 'cacti') {
		return $text;
	}

	function __n($single, $plural, $number, $domain = 'cacti') {
		return ($number == 1) ? $single : $plural;
	}

	function __() {

		$args = func_get_args();
		$num  = func_num_args();

		/* this should not happen */
		if ($num < 1) {
			return false;

		/* convert pure text strings */
		}elseif ($num == 1) {
			return $args[0];

		/* convert pure text strings by using a different textdomain */
		}elseif ($num == 2 && isset($l10n[$args[1]])) {
			return $args[0];

		/* convert stings including one or more placeholders */
		}else {

			/* only the last argument is allowed to initiate
			the use of a different textdomain */

			/* process return string against input arguments */
			return call_user_func_array('sprintf', $args);
		}
	}

	function __date($format, $timestamp = false, $domain = 'cacti') {
		if (!$timestamp) {$timestamp = time();}
		return date($format, $timestamp);
	}
}



/**
 * get_list_of_locales - returns the default settings being used for l10n
 *
 * @return - a multi-dimensional array with the locale code as main key
 */
function get_list_of_locales ()
{
	/* include to get the array for languages, but do not global it */
	include(CACTI_BASE_PATH . '/include/global_arrays.php');

	return $lang2locale;
}


/**
 * get_installed_locales - finds all installed locales
 *
 * @return - an associative array of all installed locales (e.g. 'en' => 'English')
 */
function get_installed_locales(){
	global $lang2locale;

	$locations = array();
	$supported_languages['en'] = $lang2locale['en']['language'];

	foreach($lang2locale as $locale => $properties) {
		$locations[$properties['filename'] . '.mo'] = array('locale' => $locale, 'language' => $properties['language']);
	}

	/* create a list of all languages this Cacti system supports ... */
	$dhandle = opendir(CACTI_BASE_PATH . '/locales/LC_MESSAGES');
	while (false !== ($filename = readdir($dhandle))) {
		/* check if language file for DHTML calendar is also available */
		$path2calendar = CACTI_BASE_PATH . '/include/js/jquery/locales/LC_MESSAGES/jquery.ui.datepicker-' . str_replace('.mo', '.js', $filename);
		if(isset($locations[$filename]) & file_exists($path2calendar)) {
			$supported_languages[$locations[$filename]['locale']] = $locations[$filename]['language'];
		}
	}

	return $supported_languages;
}
