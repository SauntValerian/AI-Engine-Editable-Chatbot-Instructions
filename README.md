# AI Engine Editable Chatbot Instructions
A front-end interface to display and edit chatbot instructions.

><em>This is AI-coded by Claude AI, and was created for a specific use case to allow refinement of chatbot instructions by selected users in a development and evaluation environment.</em>
>
><em>Not recommended for production sites.</em>
>Source: https://claude.ai/public/artifacts/3ffd04c9-36a6-4710-9f67-8113a823542a

This plugin simply creates a shortcode where you can display the instructions for the identified chatbot. Has options to edit the instructions and save them back to the database. By default, only Admins can edit the instructions. However, you can set an option for the minumum role needed to enable editing for other users.

### Usage

For simple display of the instructions use the shortcode this way, adding the chatbot ID.

<code>[chatbot_instructions name="chatbot-id"]</code>

Enable editing this way, by default it only allows admins to edit.

<code>[chatbot_instructions name="chatbot-id" editable="1"]</code>

Allow editing by lower roles. Set the minumum role and all above it can edit. Does not support custom user roles, accepts only organic WP user roles all the way down to Subscriber. Guests can never edit.

<code>[chatbot_instructions name="chatbot-id" editable="1" min_role="contributor"]</code>

### Debugging

This shortcode will output to Admins only and will display the information from the mwai_chatbots field from the database. Formatted for easy reading.

<code>[debug_mwai_chatbots]</code>
