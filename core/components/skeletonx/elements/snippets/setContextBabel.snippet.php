<?php
/**
 * setContextBabel (experimental)
 *
 * DESCRIPTION
 * Auto set context settings in every context for Babel
 *
 * AUTHOR
 * BART! http://github.com/bartholomej
 *
 * USAGE
 * Just call once somewhere like this [[!setContextBabel]]. Then remove ;)
 *
 */

//  Get all front contexts
    $contexts = array();
    $ctx = $modx->getCollection('modContext', array('key:NOT IN' => array('mgr')));

    foreach ($ctx as $context) {
        $contexts[] = $context->get('key');
    }
    
    $site_url = $modx->getOption('site_url');
    
    $settings = array();
    foreach ($contexts as $context) {
    //  required settings
        $settings[] = array ('key' => 'base_url',   'value' => '/',         'context_key' => $context,);
        $settings[] = array ('key' => 'cultureKey', 'value' => $context,    'context_key' => $context,);
        $settings[] = array ('key' => 'site_start', 'value' => '1',         'context_key' => $context,);
        $settings[] = array ('key' => 'site_url',   'value' => $site_url,   'context_key' => $context,);
    }
      /* JSON style
        $settings[] = '
            {"key": "base_url",     "value": "/", "context_key": "'.$context.'"},
            {"key": "cultureKey",   "value": "'.$context.'", "context_key": "'.$context.'"},
            {"key": "site_start",   "value": "1", "context_key": "'.$context.'"},
            {"key": "site_url",     "value": "'. $site_url . '", "context_key": "'.$context.'"}
        '; 
        */
        
    foreach($settings as $val){    
        $setContext = $modx->newObject('modContextSetting');
        $setContext->fromArray($val, '', true);
        $setContext->set('xtype', 'textfield');
        $setContext->set('namespace', 'core');
        $setContext->set('area', 'language');
        $setContext->save();
    }
    //  Clear context cache //TODO
        $cacheRefreshOptions =  array( 'context_settings' => array('contexts' => array($contexts) ));
        $modx->cacheManager-> refresh($cacheRefreshOptions);
        
    return "[setContextBabel]: Babel settings created for all contexts. Now you should remove snippet call." . var_dump($contexts);