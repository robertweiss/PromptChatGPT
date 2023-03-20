# ProcessChatGPT – A Processwire module to process a field value with the Chat GPT API

ProcessChatGPT is a module for the CMS Processwire which is triggered on save and processes the value of a page field using Chat GPT. The processed value can be saved back to the field or to another field on the same page.

### Installation
1. Download and install [ProcessGPT](https://github.com/robertweiss/ProcessChatGPT)
4. Configure the module settings if needed (Chat GPT Key is required, [you can get one here](https://platform.openai.com/account/api-keys))
6. Open a page, click on the arrow next to the save-button, choose ›Save + send to ChatGPT‹

### Settings
- ChatGPT API Key (required)
- Source Field (Field which will be processed)
- Target Field (Field which is used to save the response)
- Commando string for ChatGPT (Will be prefixed to the source field so you can add hints what to do with the text, e.g. ›write a summary with max. 400 characters of the following text:‹)

### Field support
- PageTitle(Language)
- Text(Language)
- Textarea(Language)

**Please note, this is an alpha release.** Please use in production only after thorough testing for your own project and create Github issues for bugs found if possible.****
