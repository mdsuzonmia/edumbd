<?php

//use OpenAI;


if (!function_exists('generate_ai_single_question')) {
    function generate_ai_single_question($topic, $type, $custom_prompt = '')
    {
        $apiKey = get_setting_value('openai_api_key'); // Fetch OpenAI API key
        $model  = get_setting_value('open_ai_model');  // Fetch selected AI model
        $client = OpenAI::client($apiKey);

        // Base prompt for generating a question
        $prompt = "Generate a single {$type} question for the topic: {$topic}.";

        if (!empty($custom_prompt)) {
            $prompt .= " Additional instructions: {$custom_prompt}";
        }

        // Adjust the prompt to use your required format for options
        $prompt .= " Return the response in JSON format like this:
            {
                \"question\": \"Your generated question here\",
                \"options\": {
                    \"A\": \"Option A description\",
                    \"B\": \"Option B description\",
                    \"C\": \"Option C description\",
                    \"D\": \"Option D description\"
                },  // Only for MCQ and True/False else blank
                \"correct_option\": \"A\",  // Only for MCQ and True/False else blank
                \"answer\": \"Correct answer here\", // Only for Fill in the Blank, Descriptive and Subjective else blank
                \"difficulty\": \"easy\" // Options: easy, medium, hard
            }";

        // Call OpenAI API
        $response = $client->chat()->create([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 300,
        ]);

        // Decode JSON response
        $data = json_decode($response['choices'][0]['message']['content'], true);

        // Prepare the final array structure
        $finalData = [
            "options" => $data['options']
        ];

        // Convert the array into JSON format
        $option_json_data = json_encode($finalData, JSON_PRETTY_PRINT);

        //Ensure default values to avoid errors
        return [
            'question'       => $data['question'] ?? '',
            'options'        => $option_json_data ?? [],
            'correct_option' => $data['correct_option'] ?? '',
            'answer'         => $data['answer'] ?? '',
            'difficulty'     => $data['difficulty'] ?? 'easy'
        ];

       
    }


}

if (!function_exists('generate_ai_multiple_question')) {
    function generate_ai_multiple_question($topic, $custom_prompt = '', $question_counts = [])
    {
        $apiKey = get_setting_value('openai_api_key'); // Fetch OpenAI API key
        $model  = get_setting_value('open_ai_model');  // Fetch selected AI model
        $client = OpenAI::client($apiKey);

        // Define question types and their counts
        $default_question_counts = [
            'mcq' => 5,
            'true/false' => 5,
            'fill in the blank' => 5,
            'descriptive' => 5,
            'subjective' => 5
        ];

        if(!$question_counts){
            $question_counts = $default_question_counts;
        }

        // Construct the prompt dynamically
        $prompt = "Generate questions for the topic: '{$topic}'.\n";

        foreach ($question_counts as $type => $count) {
            if ($count > 0) {
                $prompt .= "Generate {$count} {$type} questions.\n";
            }
        }

        if (!empty($custom_prompt)) {
            $prompt .= "Additional instructions: {$custom_prompt}\n";
        }

        // Define response format
        $prompt .= "Return the response in JSON array format like this:
        [
            {
                \"question\": \"Your generated question here\",
                \"options\": {
                    \"A\": \"Option A description\",
                    \"B\": \"Option B description\",
                    \"C\": \"Option C description\",
                    \"D\": \"Option D description\"
                },  // Only for mcq and true/false else blank 
                \"correct_option\": \"A\",  // Only for mcq and true/false else blank
                \"answer\": \"Correct answer here\", // Only for Fill in the Blank, Descriptive and Subjective else blank
                \"difficulty\": \"easy\" // Options: easy, medium, hard
                \"type\": \"mcq\" // Options: mcq, true/false, fill in the blank, descriptive, subjective
            }
        ]";

        // Call OpenAI API
        $response = $client->chat()->create([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 1500, // Increase token limit for multiple questions
        ]);

        // Decode JSON response
        $data = json_decode($response['choices'][0]['message']['content'], true);

        // Ensure response is a valid array
        if (!is_array($data)) {
            return ['error' => 'Invalid AI response'];
        }

        return $data;
    }
}

if (!function_exists('generate_ai_question')) {
    function generate_ai_question($examTopic, $numQuestions = '')
    {
        $apiKey = get_setting_value('openai_api_key'); // Replace with your OpenAI API key
	    $model  = get_setting_value('open_ai_model');
        $client = OpenAI::client($apiKey);

        // Prepare the completions model request
        // $prompt = "Generate $numQuestions multiple-choice questions about '$examTopic'. Each question should be formatted as JSON with fields: question, options (A, B, C, D), correct_option, difficulty (easy, medium, hard), and type (MCQ, True/False, Short Answer). Example format:
        //     [
        //         {
        //             'question': 'What is PHP?',
        //             'options': { 'A': 'A language', 'B': 'A framework', 'C': 'A database', 'D': 'None' },
        //             'correct_option': 'A',
        //               'answer': 'Correct answer here'
        //             'difficulty': 'easy',
        //             'type': 'MCQ'
        //         }
        //     ]";
    
        // $response = $client->completions()->create([
        //     'model' => $model,
        //     'prompt' => $prompt,
        //     'max_tokens' => 500
        // ]);

        //$jsonData = trim($response['choices'][0]['text']);

        // Define AI prompt for structured question generation
        $prompt = "Generate $numQuestions diverse exam questions on '$examTopic'. 
        The questions should include:
        - MCQ (Multiple Choice Questions) with 4 options (A, B, C, D) and correct_option.
        - True/False questions.
        - Fill in the Blank questions.
        - Descriptive (Short Answer) questions.
        - Subjective (Essay-Based) questions.
        Provide the output in JSON format as an array of objects, with fields:
        {
            'type': 'MCQ' | 'True/False' | 'Fill in the Blank' | 'Descriptive' | 'Subjective',
            'question': 'Question text here',
            'options': ['A', 'B', 'C', 'D'], (only for MCQ)
            'correct_option': 'B', (only for MCQ & True/False)
            'difficulty': 'easy' | 'medium' | 'hard'
        }";

        // Request AI-generated questions
        $response = $client->chat()->create([
            'model' => $model, // Use 'gpt-4' for better accuracy
            'messages' => [
                ['role' => 'system', 'content' => 'You are an AI that generates structured exam questions.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1500,
        ]);

        // Extract AI-generated content
        $jsonData = trim($response['choices'][0]['message']['content']);


        return json_decode($jsonData, true);
    }
}

function evaluateAnswerWithAI($answer, $correct_answer) {
    $apiKey = get_setting_value('openai_api_key'); // Replace with your OpenAI API key
	$model  = get_setting_value('open_ai_model');
    $client = OpenAI::client($apiKey);

    $prompt = "Evaluate the student's answer:\n\n"
            . "Student Answer: $answer\n"
            . "Correct Answer: $correct_answer\n"
            . "Score the answer out of 10 and explain the reasoning.";

    $response = $client->completions()->create([
        'model' => $model,
        'prompt' => $prompt,
        'max_tokens' => 150,
    ]);

    return $response['choices'][0]['text'];
}

