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
        $field = wire('modules')->get($fieldNameId);

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
            'sourceField' => [],
            'targetField' => [],
            'commandoString' => '',
        ];
    }

    private function getFieldOptions() {
        $fieldsOptions = [];
        if (wire('fields')) {
            foreach (wire('fields') as $field) {
                if ($field->flags && $field->flags === Field::flagSystem) {
                    continue;
                }
                if (!in_array(get_class($field->type), $this->textFieldTypes)) {
                    continue;
                }

                $label = $field->label ? $field->label.' ('.$field->name.')' : $field->name;
                $fieldsOptions[$field->name] = $label;
            }
        }

        return $fieldsOptions;
    }

    public function getInputFields() {
        $inputfields = parent::getInputfields();

        $inputfields->add(
            $this->buildInputField('InputfieldText', [
                'name+id' => 'apiKey',
                'label' => $this->_('ChatGPT API Key'),
                'description' => $this->_('You need a ChatGPT API key to use this module. API keys can be generated here: https://platform.openai.com/account/api-keys'),
                'columnWidth' => 100,
                'required' => true,
            ])
        );

        $inputfields->add(
            $this->buildInputField('InputfieldText', [
                'name+id' => 'commandoString',
                'label' => $this->_('Commando string for ChatGPT'),
                'description' => $this->_('This text will be prefixed to the content of the source field before it will be sent to ChatGPT. You can use it as the commando what to do with the source field'),
                'columnWidth' => 34,
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
                'columnWidth' => 33,
            ])
        );

        return $inputfields;
    }
}
