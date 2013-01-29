<?php
/**
 * Template functions for this plugin
 * 
 * Place all functions that may be usable in theme template files here.
 * 
 * @package PluginTemplate
 * 
 * @author kynatro
 * @version 1.0.0
 * @since 1.0.0
 */

function print_content_sections_toc() {
	echo ContentSections()->get_the_toc();
}