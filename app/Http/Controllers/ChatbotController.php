<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    /**
     * Handle the incoming chat message.
     *
     * @return Response
     */
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string',
            ]);

            $message = $request->input('message');

            // Default response if Ollama is not connected
            $response = "I'm currently unable to connect to the AI service. Please try again later.";

            try {
                // Replace with your actual Ollama API endpoint
                // Assuming Ollama is running locally on port 11434
                $ollamaUrl = config('services.ollama.url');
                $model = config('services.ollama.model');

                // Check if we can connect to Ollama
                $ollamaResponse = Http::timeout(5)->post($ollamaUrl, [
                    'model' => $model,
                    'prompt' => $message,
                    'stream' => false,
                ]);

                if ($ollamaResponse->successful()) {
                    $data = $ollamaResponse->json();
                    $response = $data['response'] ?? "I received your message but couldn't generate a proper response.";
                } else {
                    // Fallback to simple keyword matching if API fails
                    $response = $this->getFallbackResponse($message);
                }
            } catch (\Exception $e) {
                // Fallback if connection error
                // Log::error('Ollama connection error: ' . $e->getMessage());
                $response = $this->getFallbackResponse($message);
            }

            return response()->json([
                'response' => $response,
                'status' => 'success',
            ]);

        } catch (\Throwable $e) {
            // Catch any other errors (validation, etc) and return a safe response
            // Log::error('Chatbot General Error: ' . $e->getMessage());
            return response()->json([
                'response' => 'I encountered a system error: '.$e->getMessage(),
                'status' => 'error',
            ]);
        }
    }

    /**
     * Get a fallback response based on keywords.
     *
     * @param  string  $message
     * @return string
     */
    private function getFallbackResponse($message)
    {
        $lowerMsg = strtolower($message);

        if (str_contains($lowerMsg, 'hello') || str_contains($lowerMsg, 'hi')) {
            return 'Hi there! Welcome to HOWSEY. How can I assist you with your property management needs today?';
        } elseif (str_contains($lowerMsg, 'rent') || str_contains($lowerMsg, 'pay')) {
            return "You can manage payments in the 'Payments' section of your dashboard. We accept various payment methods for your convenience.";
        } elseif (str_contains($lowerMsg, 'tenant')) {
            return "To manage tenants, visit the 'Tenants' page from the sidebar menu. You can add new tenants, view details, and manage leases there.";
        } elseif (str_contains($lowerMsg, 'maintenance') || str_contains($lowerMsg, 'repair')) {
            return "Maintenance requests can be submitted through the 'Maintenance' tab. You can track the status of your requests there as well.";
        } elseif (str_contains($lowerMsg, 'contact') || str_contains($lowerMsg, 'support')) {
            return 'You can reach our support team at support@howsey.com or call us at (555) 123-4567 during business hours.';
        }

        return "I'm currently running in offline mode. To enable full AI capabilities, please ensure the Ollama service is running and configured correctly.";
    }
}
