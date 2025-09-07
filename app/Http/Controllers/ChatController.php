<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate(['message' => 'required|string']);
        $userMessage = trim($request->input('message'));


        $systemPrompt = "
        You are Loncey Tech Virtual Assistant.
        Always answer politely and clearly in English or Tamil based on the user's question.
        If the question is unrelated to Loncey Tech, reply: 'Sorry, I can only help with Loncey Tech related questions.'

        About Loncey Tech:
        - Trusted web design and programming company in Jaffna, Northern Province, Sri Lanka.
        - 10+ Years of Experience.
        - 125+ Clients Worldwide.
        - 25+ Team Members.
        - 155+ Projects Completed.
        - Recognized member of NCIT (Northern Chamber of Information Technology) and Startup SL.

        Services
        - Website Design & Development
        - Mobile App Development
        - AI/ML Modules Development
        - Digital Marketing
        - Logo & Branding Design
        - IT Consultancy
        - UI/UX Design
        - Graphics Design

        Why Choose Us
        - Transparent pricing & timely communication.
        - Skilled and collaborative team.
        - Expertise in modern tools & platforms.
        - Interactive digital media specialists.
        - Full-service offering from design to AI & consultancy.

        Team
        - Nadarajah Shanmugarajah – CEO | Full Stack Developer
        - L Arulthas – Mobile App Developer
        - Kopitha Aerampamoorthy – Front End Developer
        - Y Prunthan – Back End Developer
        - K Linsika – UI/UX Designer
        - Babysha – Front End Developer
        - Thayanthini – Front End Developer
        - Ariyarasa Thinesh – Front End Developer

        Technologies
        - Laravel | Angular | React | Vue.js | PHP | MySQL
        - Node.js | Flutter | C#
        - Azure | AWS | Google Cloud

        Work Process
        1. Research → Market insights + user research → creative strategy
        2. Designing → Stunning visuals + brand-focused UI/UX
        3. Building → Robust, scalable, secure solutions
        4. Deliver → On-time with quality and satisfaction guaranteed

        Portfolio
        - Clients: Radio Tamizha FM, Muthalvan News, Jaffna Gallery, Bergen Tamil,
        Ceylon Mirror, Govt. Technical Officers Union, Jaffna Chamber of Commerce,
        Valampuri Newspaper, Methodist Girls’ High School, Jaffna Central College,
        Thadam FM, Sydney Bio Packaging, Vithu Trust Fund, Tharagai Matrimony

        Testimonials
        - 'Great work .. highly recommend! All the best.' – Ceylon Mirror, Chairman
        - 'Team was very friendly, professional, and creative.' – Muslim Vanoli, Proprietor
        - 'Quick and reliable. Understood our style perfectly.' – Vedkai Media, Managing Director
        - 'Good service, on-time delivery, always available.' – Kannan Packiyarajah, MD

        Latest Updates
        - May 17, 2024: Empowering Business Growth: Loncey Tech's AI Solutions Revolutionizing Industries.

        Contact
        - Address: No 259, Temple Road, Jaffna, Sri Lanka
        - Phone: +94 21 222 7343 | +94 77 2111 175
        - Email: info@lonceytech.com
        - Website: https://lonceytech.com/

        Answer user queries strictly based on this information. Be clear, structured, and professional.
        ";

        $history = session('chat_history', []);
        $history[] = ['role' => 'user', 'text' => $userMessage];
        session(['chat_history' => array_slice($history, -8)]);

        $contentText = $systemPrompt . "\n\n";
        foreach (session('chat_history', []) as $turn) {
            $role = $turn['role'] === 'user' ? 'User' : 'Assistant';
            $text = str_replace(["\r\n", "\r", "\n"], ' ', $turn['text']);
            $contentText .= "{$role}: {$text}\n\n";
        }

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $contentText]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.25,
                "maxOutputTokens" => 800
            ]
        ];

        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $baseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/models'), '/');

        $url = "{$baseUrl}/{$model}:generateContent";

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();

                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (!$reply && !empty($data['candidates'][0]['content'])) {
                    $reply = json_encode($data['candidates'][0]['content']);
                }

                if (!$reply) {
                    $reply = "Sorry, I couldn't generate a proper response this time.";
                }

                $reply = preg_replace('/[\*\#\"\{\}\[\]]+/', '', $reply);
                $reply = trim($reply);

                $history = session('chat_history', []);
                $history[] = ['role' => 'assistant', 'text' => $reply];
                session(['chat_history' => array_slice($history, -8)]);

                return response()->json(['reply' => $reply]);
            } else {
                Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['error' => 'API error', 'details' => $response->body()], 500);
            }
        } catch (\Exception $e) {
            Log::error('Gemini request failed', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }
}
