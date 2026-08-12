<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Frontend\Customer\Customer;
use App\Events\MessageSent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        if (!session()->get('is_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = session()->get('user');
        $customer = Customer::where('user_id', $user['id'])->first();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $conversation = Conversation::firstOrCreate([
            'customer_id' => $customer->id,
        ]);

        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ]);
    }

    public function store(Request $request)
    {
        if (!session()->get('is_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'text' => 'required|string',
        ]);

        $user = session()->get('user');
        $customer = Customer::where('user_id', $user['id'])->first();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $conversation = Conversation::firstOrCreate([
            'customer_id' => $customer->id,
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $customer->id,
            'sender_type' => 'customer',
            'text' => $request->text,
        ]);

        // Broadcast to Pusher
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}
