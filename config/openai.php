<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key and Organization
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
    | and organization on your OpenAI dashboard, at https://openai.com.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'project' => env('OPENAI_PROJECT'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will time out after 30 seconds.
    |
    */

    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Base URI
    |--------------------------------------------------------------------------
    |
    | You may specify a custom base URI for the OpenAI API. This is useful
    | if you are using a proxy or custom endpoint.
    |
    | Examples:
    | - Groq: https://api.groq.com/openai/v1
    | - Together AI: https://api.together.xyz/v1
    | - OpenRouter: https://openrouter.ai/api/v1
    |
    */

    'base_uri' => env('OPENAI_BASE_URI'),

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    |
    | The AI model to use for content generation. Default is gpt-4o-mini.
    |
    | OpenAI Models: gpt-4o-mini, gpt-4o, gpt-3.5-turbo
    | Groq Models: llama-3.1-8b-instant, mixtral-8x7b-32768, gemma2-9b-it
    | Together AI: meta-llama/Meta-Llama-3.1-8B-Instruct-Turbo
    |
    */

    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

];
