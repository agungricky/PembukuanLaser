<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatKlarifikasiPacking;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel(
    'packing-chat.{chatId}',
    function ($user, $chatId) {

        // ID chat harus valid.
        if (!ctype_digit((string) $chatId)) {
            return false;
        }

        $chat = ChatKlarifikasiPacking::find((int) $chatId);

        if (!$chat) {
            return false;
        }

        // Packing hanya boleh mengakses chat buatannya.
        if ($user->role === 'packing') {
            return (int) $chat->packing_user_id === (int) $user->id;
        }

        // CS hanya boleh mengakses chat pesanan miliknya.
        if ($user->role === 'cs') {
            return (int) $chat->cs_user_id === (int) $user->id;
        }

        // Role lainnya tidak diizinkan.
        return false;
    },
    ['guards' => ['sanctum']]
);
Broadcast::channel('chat-inbox-user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId
        && $user->platform === 'meta'
        && in_array($user->role, ['cs', 'packing'], true);
});
