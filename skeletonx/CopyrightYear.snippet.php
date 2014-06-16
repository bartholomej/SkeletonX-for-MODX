<?
/**
 * CopyrightYear: get years to copyright footer
 *
 * Parameters:
 * 	start [optional]: First year of website. 
 * 		Special param: modx (Get modx installation date)
 * 		DEFAULT: nothing.
 * 	separator [optional]: the space string between start and current year
 *
 * 
 * EXAMPLES: e.g. current year: 2014, MODx installed: 2013
 * [[CopyrightYear]]				// return: 2014
 * [[CopyrightYear? &start=`2010`]]	// return: 2010 - 2014
 * [[CopyrightYear? &start=`modx`]]	// return: 2013 - 2014
 * 
 * Author: BART!
 */
 
$start = $modx->getOption('start', $scriptProperties, date("Y"));
$separator = $modx->getOption('separator', $scriptProperties, ' - ');
 
$now = date("Y");
$start = isset($start)? $start : $now;

if($start == 'modx'){ // Dirty, dirty way ho to get installation date :))
    $setting = $modx->getObject('modSystemSetting', 'emailsender');
        if($setting){
            $settings = $setting->toArray();
            $start = date('Y', strtotime($settings['editedon']));
        }else{
            $modx->log(modX::LOG_LEVEL_ERROR, '[CopyrightYear] Cant get date from system settings property!');
        }
}    

$years = ($now > $start && $start > '1970') ? $start . $separator . $now : $now;
 
return $years;