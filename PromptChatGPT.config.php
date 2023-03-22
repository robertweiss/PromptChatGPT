<?php namespace ProcessWire;

class PromptChatGPTConfig extends ModuleConfig {
    // Parts of the code are adopted from the Jumplinks module, thx!
    // Copyright (c) 2016-17, Mike Rockett

    private $textFieldTypes = [
        'ProcessWire\FieldtypePageTitle',
        'ProcessWire\FieldtypePageTitleLanguage',
        'ProcessWire\FieldtypeText',
        'ProcessWire\FieldtypeTextarea',
        'ProcessWire\FieldtypeTextLanguage',
        'ProcessWire\FieldtypeTextareaLanguage',
    ];

    protected function buildInputField($fieldNameId, $meta) {
        $field = modules()->get($fieldNameId);

        foreach ($meta as $metaNames => $metaInfo) {
            $metaNames = explode('+', $metaNames);
            foreach ($metaNames as $metaName) {
                $field->$metaName = $metaInfo;
            }
        }

        return $field;
    }

    public function getDefaults() {
        return [
            'apiKey' => '',
            'includedTemplates' => [],
            'sourceField' => [],
            'targetField' => [],
            'commandoString' => '',
        ];
    }

    private function getFieldOptions() {
        $fieldsOptions = [];
        if (fields()) {
            foreach (fields() as $field) {
                if ($field->flags && $field->flags === Field::flagSystem) {
                    continue;
                }
                if (!in_array(get_class($field->type), $this->textFieldTypes)) {
                    continue;
                }

                $label = $field->label ? $field->name.' ('.$field->label.')' : $field->name;
                $fieldsOptions[$field->name] = $label;
            }
        }

        ksort($fieldsOptions);

        return $fieldsOptions;
    }

    private function getTemplateOptions() {
        $templatesOptions = [];
        if (templates()) {
            foreach (templates() as $template) {
                if ($template->flags && $template->flags === Template::flagSystem) {
                    continue;
                }
                $label = $template->label ? $template->name.' ('.$template->label.')' : $template->name;
                $templatesOptions[$template->name] = $label;
            }
        }

        ksort($templatesOptions);

        return $templatesOptions;
    }

    public function getInputFields() {
        $inputfields = parent::getInputfields();

        $inputfields->add(
            $this->buildInputField('InputfieldText', [
                'name+id' => 'apiKey',
                'label' => $this->_('ChatGPT API Key'),
                'description' => $this->_('You need a ChatGPT API key to use this module. API keys can be generated here: https://platform.openai.com/account/api-keys'),
                'columnWidth' => 50,
                'required' => true,
            ])
        );

        $inputfields->add(
            $this->buildInputField('InputfieldText', [
                'name+id' => 'commandoString',
                'label' => $this->_('Commando string for ChatGPT'),
                'description' => $this->_('This text will be prefixed to the content of the source field before it will be sent to ChatGPT. You can use it as the commando what to do with the source field'),
                'columnWidth' => 50,
            ])
        );

        $inputfields->add(
            $this->buildInputField('InputfieldASMSelect', [
                'name+id' => 'includedTemplates',
                'label' => $this->_('Templates'),
                'description' => $this->_('Pages with these templates will display the save + send to ChatGPT option in the save dropdown. If no selection is made, the option will be shown on all pages'),
                'options' => $this->getTemplateOptions(),
                'columnWidth' => 33,
            ])
        );

        $inputfields->add(
            $this->buildInputField('InputfieldSelect', [
                'name+id' => 'sourceField',
                'label' => $this->_('Source Field'),
                'description' => $this->_('The field which will be sent to ChatGPT. If no selection is made, only the commando string is sent'),
                'options' => $this->getFieldOptions(),
                'columnWidth' => 33,
            ])
        );

        $inputfields->add(
            $this->buildInputField('InputfieldSelect', [
                'name+id' => 'targetField',
                'label' => $this->_('Target Field'),
                'description' => $this->_('The field which will be replaced by the answer of ChatGPT. If no selection is made, the response will be shown as a system notice'),
                'options' => $this->getFieldOptions(),
                'columnWidth' => 34,
            ])
        );

        $inputfields->add(
            $this->buildInputField('InputfieldCheckbox', [
                'name+id' => 'test_settings',
                'label' => $this->_('Test settings on save'),
                'description' => $this->_('Send a test request to Chat GPT'),
                'value' => 1,
                'checked' => '',
                'columnWidth' => 100,
            ])
        );

        if (input()->post('test_settings')) {
            session()->set('test_settings', 1);
        }

        if (session()->get('test_settings')) {
            $inputfields->add(
                $this->buildInputField('InputfieldMarkup', [
                    'name+id' => 'debug_log',
                    'label' => $this->_('Test results'),
                    'options' => $this->getFieldOptions(),
                    'columnWidth' => 100,
                    'value' => $this->requestTest(),
                ])
            );

            // Uncheck test_settings to prevent testing next time the module config is shown
            $moduleConfig = modules()->getConfig('PromptChatGPT');
            $moduleConfig['test_settings'] = 0;
            modules()->saveConfig('PromptChatGPT', $moduleConfig);

            if (!input()->post('test_settings') && session()->get('test_settings')) {
                session()->remove('test_settings');
            }
        }

        return $inputfields;
    }

    private function requestTest() {
        $module = modules('PromptChatGPT');
        $test = $module->testConnection();

        return '<pre>'.$test.'</pre>';
    }
}

