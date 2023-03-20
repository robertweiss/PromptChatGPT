<?php namespace ProcessWire;

require_once __DIR__.'/vendor/autoload.php';

use Orhanerday\OpenAi\OpenAi;
class ProcessChatGPT extends Process implements Module {
    private $apiKey;
    private $chatGPT;
    private $sourceField;
    private $targetField;
    private $commandoString;
    private $throttleSave;

    public function init() {
        if ($this->initSettings()) {
            $this->addHookAfter("ProcessPageEdit::getSubmitActions", $this, "addDropdownOption");
            $this->addHookAfter("Pages::saved", $this, "hookPageSave");
        }

        parent::init();
    }

    public function initSettings() {
        // Set (user-)settings
        $this->apiKey = $this->get('apiKey');
        $this->sourceField = $this->get('sourceField');
        $this->targetField = $this->get('targetField');
        $this->commandoString = $this->get('commandoString');
        $this->throttleSave = 5;

        if (!$this->apiKey) {
            return false;
        }

        $this->chatGPT = new OpenAi($this->apiKey);

        return true;
    }

    public function hookPageSave($event) {
        /** @var Page $page */
        $page = $event->arguments('page');

        // Only start the magic if post variable is set
        if (strpos($this->input->post->_after_submit_action, 'save_and_chat') !== 0) {
            return;
        }

        // Throttle processing (only triggers every after a set amount of time)
        if ($this->page->modified > (time() - $this->throttleSave)) {
            $this->error(__('Please wait some time before you try to send again.'));

            return;
        }

        // Let’s go!
        $this->processField($page);
    }

    public function addDropdownOption($event) {
        $actions = $event->return;

        $label = "%s + ".__('send to ChatGPT');

        $actions[] = [
            'value' => 'save_and_chat',
            'icon' => 'magic',
            'label' => $label,
        ];

        $event->return = $actions;
    }

    private function getAnswer(string $value) {
        // Trim to max. 10000 chars, which is hopefully less than 4096 tokens
        // https://platform.openai.com/tokenizer
        $sanitizedValue = sanitizer()->trim(sanitizer()->getTextTools()->markupToText($value), 10000);
        $content = trim($this->commandoString.' '.$sanitizedValue);
//        ray($content);

        $chat = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
//            'temperature' => 1.0,
//            'max_tokens' => 4000,
//            'frequency_penalty' => 0,
//            'presence_penalty' => 0,
        ];

        $result = $this->chatGPT->chat($chat);
        $result = json_decode($result);
        $resultText = $result->choices[0]->message->content;
//        ray($resultText);

        return $resultText;
    }

    private function processField(Page $page) {
        $page->of(false);
        $fields = $page->template->fields;
        $sourceField = null;

        foreach ($fields as $field) {
            // Only process source field
            if ($field->name === $this->sourceField) {
                $sourceField = $field;
                break;
            }
        }

        if (!$sourceField) {
            return;
        }

        $value = $page->get($sourceField->name);
        $result = $this->getAnswer($value);

        if (!$result) {
            return;
        }

        $target = $this->targetField;
        // Check if target field even exists before saving into the void
        if ($page->get($target) !== null) {
            $page->setAndSave($target, $result, ['noHook' => true]);
        } else {
            $this->message($result);
        }
    }
}
