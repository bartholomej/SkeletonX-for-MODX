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
    
    // Generate gateway plugin (hardcoded instructions)
    
    $gateway = '<h2>Step #3 - Creating a "gateway" plugin</h2>
                We will need to create a basic plugin to assign the correct context.<br>
                In the MODX Manager go to <strong>Elements</strong> and click on the <strong>New Plugin</strong> icon.<br>
                Name the plugin <strong>gateway</strong> and add the following Plugin code:<br>
                ';
    $gateway .= '<textarea rows="25" cols="150">';
    $gateway .= (htmlentities('
                <?php 
                /* SkeletonX generated gateway plugin for babel: https://github.com/bartholomej/SkeletonX-for-MODX */
                    if($modx->context->get(\'key\') != "mgr"){ 
                    switch($_REQUEST[\'cultureKey\']) { 
                        '));
    
    foreach ($contexts as $context) {
        $gateway .= (htmlentities('
                            case \''.$context.'\':
                                $modx->switchContext(\''.$context.'\');
                                break;   
                    '));
    }                
    $gateway .= (htmlentities('
                            default:
                                /* Set the default context here */
                                $modx->switchContext(\'web\');
                                break;
                    }
                /* unset GET var to avoid
                 * appending cultureKey=xy to URLs by other components */
                unset($_GET[\'cultureKey\']);
            }
            '));
    $gateway .= '</textarea>';
    $gateway .= '<br>This gateway plugin will need to be activated by a OnHandleRequest, to do this go to the <strong>System Events</strong> tab, scroll down to the <strong>OnHandleRequest</strong> line and enable this.<br>
                 Save the <strong>gateway</strong> plugin.';

    // generate htaccess
    
    $htaccess = '<h2>Step #1 - Setting up .htaccess</h2>';
    $htaccess .= '<textarea rows="25" cols="150">';
    $htaccess .= '
        # redirect all requests to /en/favicon.ico and /cs/favicon.ico
        # to /favicon.ico
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(cs|en)/favicon.ico$ favicon.ico [L,QSA]
           
        # redirect all requests to /en/assets* and /cs/assets* to /assets*
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(cs|en)/assets(.*)$ assets$2 [L,QSA]
          
        # redirect all other requests to /en/* and /cs/*
        # to index.php and set the cultureKey parameter
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(cs|en)?/?(.*)$ index.php?cultureKey=$1&q=$2 [L,QSA]
        </textarea><br>
    ';
    $ctxOutput = '<h2>Step #2 - Creating your contexts</h2>';
    $ctxOutput .= '[setContextBabel]: Babel settings created for all contexts. Go to next step.<br/><code>' . print_r($contexts, true). '</code>';
    
    $instructions = '<h2>Step #4 - Installing Babel</h2>'; 
    $instructions .= 'Now you should remove snippet call.<br/>
                      Almost there! We now can install the Babel package from package manager.<br/>
                      Based on this great manual: http://designfromwithin.com/blog-webdesign-development/2012/01/12/modx-multilingual-setting-up-babel-and-have-a-website-with-multible-languages/                  
                      ';
    
    
    
    return $htaccess . $ctxOutput .  $gateway . $instructions;
 