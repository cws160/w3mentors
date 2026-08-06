<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public const THREAD_PRIVATE = 1;

    private const THREAD_READ = 1;

    private const USER_COLORS = [
        '#FF0000', '#b021a2', '#12126b', '#3b3b80', '#694069',
        '#024D02', '#630463', '#42806b', '#808000', '#1b471b',
    ];

    public function findPrivateThread(int $userA, int $userB): ?int
    {
        $threadId = DB::table('tbl_thread_users as thusr')
            ->join('tbl_threads as thread', 'thread.thread_id', '=', 'thusr.thusr_thread_id')
            ->whereIn('thusr.thusr_user_id', [$userA, $userB])
            ->where('thread.thread_type', self::THREAD_PRIVATE)
            ->whereNull('thread.thread_deleted')
            ->groupBy('thusr.thusr_thread_id')
            ->havingRaw('COUNT(DISTINCT thusr.thusr_user_id) > 1')
            ->value('thusr.thusr_thread_id');

        return $threadId ? (int) $threadId : null;
    }

    public function ensurePrivateThread(int $senderId, int $receiverId, string $message): int
    {
        if ($existing = $this->findPrivateThread($senderId, $receiverId)) {
            if (trim($message) !== '') {
                $this->addMessage($existing, $senderId, $message);
            }

            return $existing;
        }

        if (!$this->validatePrivateParticipants($senderId, $receiverId)) {
            throw new \InvalidArgumentException('Invalid chat participants.');
        }

        return DB::transaction(function () use ($senderId, $receiverId, $message) {
            $now = now()->format('Y-m-d H:i');
            $threadId = DB::table('tbl_threads')->insertGetId([
                'thread_type' => self::THREAD_PRIVATE,
                'thread_user_id' => $senderId,
                'thread_group_id' => 0,
                'thread_created' => $now,
                'thread_updated' => $now,
            ]);

            $colors = array_slice(self::USER_COLORS, 0, 2);
            foreach ([$senderId, $receiverId] as $index => $userId) {
                DB::table('tbl_thread_users')->insert([
                    'thusr_user_id' => $userId,
                    'thusr_thread_id' => $threadId,
                    'thusr_color' => $colors[$index] ?? self::USER_COLORS[0],
                    'thusr_deleted' => null,
                    'thusr_read' => self::THREAD_READ,
                ]);
            }

            if (trim($message) !== '') {
                $this->addMessage($threadId, $senderId, $message);
            }

            return (int) $threadId;
        });
    }

    private function addMessage(int $threadId, int $senderId, string $message): void
    {
        DB::table('tbl_thread_msgs')->insert([
            'msg_thread_id' => $threadId,
            'msg_user_id' => $senderId,
            'msg_text' => $message,
            'msg_created' => now()->format('Y-m-d H:i:s'),
        ]);
        DB::table('tbl_threads')
            ->where('thread_id', $threadId)
            ->update(['thread_updated' => now()->format('Y-m-d H:i')]);
    }

    private function validatePrivateParticipants(int $senderId, int $receiverId): bool
    {
        $users = User::query()
            ->whereNull('user_deleted')
            ->whereNotNull('user_verified')
            ->where('user_active', 1)
            ->whereIn('user_id', [$senderId, $receiverId])
            ->get(['user_id', 'user_is_teacher']);

        if ($users->count() !== 2) {
            return false;
        }

        return $users->sum('user_is_teacher') > 0;
    }
}
