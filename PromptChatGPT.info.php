<?php namespace ProcessWire;

$info = array(
	'title' => 'PromptChatGPT',
	'summary' => 'Send a command and/or the content of a text field to ChatGPT and save or show the result. The used model is gpt-3.5-turbo',
	'version' => 1,
	'author' => 'Robert Weiss',
	'icon' => 'magic',
    'requires' => [
        'ProcessWire>=3.0.184'
    ],
	'href' => 'https://github.com/robertweiss/PromptChatGPT',
    'singular' => true,
    'autoload' => 'template=admin'
);
