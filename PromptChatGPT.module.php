<?php namespace ProcessWire;

require_once __DIR__.'/vendor/autoload.php';

use Orhanerday\OpenAi\OpenAi;
class PromptChatGPT extends Process implements Module {
    private $apiKey;
    private $chatGPT;
    private $includedTemplates;
    private $adminTemplates = ['admin', 'language', 'user', 'permission', 'role'];
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
        $this->includedTemplates = $this->get('includedTemplates');
        $this->sourceField = $this->get('sourceField');
        $this->targetField = $this->get('targetField');
        $this->commandoString = $this->get('commandoString');
        $this->throttleSave = 5;
        $this->chatGPT = new OpenAi($this->apiKey);

        return (bool)$this->apiKey;
    }

    public function hookPageSave($event) {
        /** @var Page $page */
        $page = $event->arguments('page');

        // Only start the magic if post variable is set
        if (!str_contains($this->input->post->text('_after_submit_action'), 'save_and_chat')) {
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
        /** @var Page $page */
        $page = $this->pages->get($this->input->get->id);

        // Don’t show option in admin templates
        if (in_array($page->template->name, $this->adminTemplates)) {
            return;
        }

        // If included templates are set, only show option if page has included template
        if (count($this->includedTemplates) && !in_array($page->template->name, $this->includedTemplates)) {
            return;
        }

        $actions = $event->return;

        $label = "%s + ".__('send to ChatGPT');

        $actions[] = [
            'value' => 'save_and_chat',
            'icon' => 'magic',
            'label' => $label,
        ];

        $event->return = $actions;
    }

    private function getAnswer(string $value):string {
        if (!$value) {
            return '';
        }
        // Trim to max. 10000 chars, which is hopefully less than 4096 tokens
        // https://platform.openai.com/tokenizer
        // No more need for that, switched model to GPT-4o mini
        // $sanitizedValue = sanitizer()->trim(sanitizer()->getTextTools()->markupToText($value), 10000);
        $sanitizedValue = $value;
        $content = trim($this->commandoString.' '.$sanitizedValue);
        $resultText = $this->chat($content);
        
        return $resultText;
    }
    
    public function chat(string $value, array $customSettings = []):string {
        if (!$value) {
            return '';
        }

        $chat = $this->buildPayload($value, $customSettings);
        try {
            $result = $this->chatGPT->chat($chat);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return '';
        }

        try {
            $result = json_decode($result, null, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->error($e->getMessage());
            return '';
        }

        if (isset($result->error)) {
            $this->error($result->error->message);

            return '';
        }

        $resultText = isset($result->choices) ? $result->choices[0]->message->content : '';

        return $resultText;
    }

    public function testConnection(array $customSettings = []) {
        $chat = $this->buildPayload(__('This is a test for ChatGPT. Do you hear me?'), $customSettings);

        try {
            $result = $this->chatGPT->chat($chat);
        } catch (\Exception $e) {
            return $e;
        }

        return json_encode([
            'request' => $chat,
            'response' => json_decode($result)
        ], JSON_PRETTY_PRINT);
    }

    private function buildPayload($content, array $customSettings) {
        return array_merge($customSettings, [
            'model' => 'gpt-4o-mini',
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
        ]);
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
