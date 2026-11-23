<?php

namespace App\Services;

use App\Models\LunaConversation;
use App\Models\LunaMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LunaCoreService
{
    protected $apiService;

    public function __construct()
    {
        $this->apiService = new LunaApiService();
    }

    public function processMessage(User $user, string $message, ?int $conversationId = null)
    {
        try {
            Log::info('LUNA: Processing message', ['user_id' => $user->id]);

            $conversation = $this->getOrCreateConversation($user, $conversationId);
            $this->saveMessage($conversation, $user, 'user', $message);

            $intent = $this->detectIntent($message);
            $response = $this->generateResponse($message, $intent);

            $this->saveMessage($conversation, null, 'assistant', $response);

            return $this->formatResponse($conversation, $response);

        } catch (\Exception $e) {
            Log::error('LUNA Error: ' . $e->getMessage());
            return [
                'success' => false,
                'conversation_id' => $conversationId ?? null,
                'message' => 'Sorry, I encountered an issue. Please try again.',
                'messages' => [],
            ];
        }
    }

    protected function detectIntent($message)
    {
        $msg = strtolower($message);
        
        if (strpos($msg, 'gold price') !== false || strpos($msg, 'gold rate') !== false || strpos($msg, 'price of gold') !== false) {
            return 'gold_price';
        }
        if (strpos($msg, 'silver price') !== false || strpos($msg, 'silver rate') !== false) {
            return 'silver_price';
        }
        if (strpos($msg, 'market') !== false || strpos($msg, 'overview') !== false) {
            return 'market_overview';
        }
        if (strpos($msg, 'purchase') !== false || strpos($msg, 'buy gold') !== false) {
            return 'purchase';
        }
        if (strpos($msg, 'sell') !== false || strpos($msg, 'sale') !== false) {
            return 'sale';
        }
        if (strpos($msg, 'invest') !== false || strpos($msg, 'investment') !== false) {
            return 'investment';
        }
        if (strpos($msg, 'production') !== false || strpos($msg, 'mining') !== false) {
            return 'production';
        }
        if (strpos($msg, 'geology') !== false || strpos($msg, 'exploration') !== false) {
            return 'geology';
        }
        if (strpos($msg, 'safety') !== false || strpos($msg, 'emergency') !== false) {
            return 'safety';
        }
        if (strpos($msg, 'company') !== false || strpos($msg, 'ngemba') !== false) {
            return 'company';
        }
        if (strpos($msg, 'help') !== false) {
            return 'help';
        }
        if (strpos($msg, 'hello') !== false || strpos($msg, 'hi') !== false || strpos($msg, 'hey') !== false) {
            return 'greeting';
        }
        if (strpos($msg, 'thanks') !== false || strpos($msg, 'thank') !== false) {
            return 'thank';
        }

        return 'general';
    }

    protected function generateResponse($message, $intent)
    {
        switch ($intent) {
            case 'gold_price':
                return $this->getGoldPrice();
            
            case 'silver_price':
                return $this->getSilverPrice();
            
            case 'market_overview':
                return $this->getMarketOverview();
            
            case 'purchase':
                return $this->getPurchaseInfo();
            
            case 'sale':
                return $this->getSaleInfo();
            
            case 'investment':
                return $this->getInvestmentInfo();
            
            case 'production':
                return $this->getProductionInfo();
            
            case 'geology':
                return $this->getGeologyInfo();
            
            case 'safety':
                return $this->getSafetyInfo();
            
            case 'company':
                return $this->getCompanyInfo();
            
            case 'greeting':
                return $this->getGreeting();
            
            case 'thank':
                return "You're welcome! 😊 Anything else I can help with?";
            
            case 'help':
                return $this->getHelp();
            
            default:
                return $this->getGeneralResponse($message);
        }
    }

    // ============================================
    // SIMPLE RESPONSES - Just the facts
    // ============================================

    protected function getGoldPrice()
    {
        $gold = $this->apiService->getGoldPrice();
        
        $response = "💰 **Gold Price:** $".number_format($gold['price'], 2)." USD/oz\n";
        $response .= "📈 Change: ".($gold['change'] >= 0 ? '+' : '').number_format($gold['change'], 2)." USD (".($gold['change_percent'] >= 0 ? '+' : '').$gold['change_percent']."%)\n";
        $response .= "🕐 Updated: ".now()->format('H:i')."\n\n";
        $response .= "Need more details? Just ask! 😊";

        return $response;
    }

    protected function getSilverPrice()
    {
        $silver = $this->apiService->getSilverPrice();
        
        $response = "🥈 **Silver Price:** $".number_format($silver['price'], 2)." USD/oz\n";
        $response .= "📈 Change: ".($silver['change'] >= 0 ? '+' : '').number_format($silver['change'], 2)." USD (".($silver['change_percent'] >= 0 ? '+' : '').$silver['change_percent']."%)\n\n";
        $response .= "Want gold price too? Just ask! 😊";

        return $response;
    }

    protected function getMarketOverview()
    {
        $market = $this->apiService->getMarketOverview();
        $data = $market['data'];
        
        $response = "📊 **Market Overview**\n\n";
        $response .= "💰 Gold: $".number_format($data['gold_price'], 2)." USD/oz\n";
        $response .= "🥈 Silver: $".number_format($data['silver_price'], 2)." USD/oz\n";
        $response .= "📊 Sentiment: {$data['market_sentiment']}\n";
        $response .= "📦 Volume: {$data['trading_volume']}\n\n";
        $response .= "Want more details? Just ask! 😊";

        return $response;
    }

    protected function getPurchaseInfo()
    {
        return "📋 **Gold Purchase Process**\n\n" .
               "1. Submit purchase request\n" .
               "2. Branch Manager approves\n" .
               "3. Finance verifies budget\n" .
               "4. Director approves\n" .
               "5. Payment processed\n" .
               "6. Gold delivered & inspected\n\n" .
               "Need step-by-step guide? Just ask! 😊";
    }

    protected function getSaleInfo()
    {
        return "💰 **Gold Sale Process**\n\n" .
               "1. Identify buyer & negotiate\n" .
               "2. Branch Manager reviews\n" .
               "3. Finance verifies payment\n" .
               "4. Director approves\n" .
               "5. Payment received\n" .
               "6. Gold delivered\n\n" .
               "Want detailed procedure? Just ask! 😊";
    }

    protected function getInvestmentInfo()
    {
        $insights = $this->apiService->getInvestmentInsights();
        $gold = $this->apiService->getGoldPrice();
        
        return "📈 **Investment Outlook**\n\n" .
               "💰 Current Gold: $".number_format($gold['price'], 2)." USD/oz\n" .
               "📊 Short-term: {$insights['short_term']['outlook']} - {$insights['short_term']['recommendation']}\n" .
               "📊 Long-term: {$insights['long_term']['outlook']} - {$insights['long_term']['recommendation']}\n" .
               "⚠️ Risk: {$insights['risk_level']}\n\n" .
               "Want detailed analysis? Just ask! 😊";
    }

    protected function getProductionInfo()
    {
        $ops = $this->apiService->getCompanyOperations();
        
        return "⛏️ **Production Status**\n\n" .
               "📦 Daily: {$ops['production']['daily']}\n" .
               "📦 Monthly: {$ops['production']['monthly']}\n" .
               "📦 Yearly: {$ops['production']['yearly']}\n" .
               "👥 Team: {$ops['employees']} employees\n" .
               "📋 Projects: {$ops['active_projects']} active\n\n" .
               "Want detailed report? Just ask! 😊";
    }

    protected function getGeologyInfo()
    {
        return "🪨 **Geology & Exploration**\n\n" .
               "• Mineral exploration & drilling\n" .
               "• Geological mapping & surveys\n" .
               "• Ore body modeling\n" .
               "• Resource estimation\n" .
               "• Sample analysis & assay\n\n" .
               "Want more details? Just ask! 😊";
    }

    protected function getSafetyInfo()
    {
        return "🦺 **Safety Guidelines**\n\n" .
               "✅ Always wear PPE\n" .
               "✅ Risk assessment first\n" .
               "✅ Permit for high-risk work\n" .
               "✅ Report hazards immediately\n" .
               "✅ Know evacuation routes\n\n" .
               "Want full safety manual? Just ask! 😊";
    }

    protected function getCompanyInfo()
    {
        return "🏢 **Ngemba Mining**\n\n" .
               "📍 HQ: Dar es Salaam\n" .
               "📍 Branches: Kahama, Geita, Mwanza, Chunya, Kakola\n" .
               "👥 200+ employees\n" .
               "⛏️ 3 processing facilities\n" .
               "📋 2 exploration projects\n\n" .
               "Want more company info? Just ask! 😊";
    }

    protected function getGreeting()
    {
        $hour = date('H');
        $time = $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening');
        
        return "Good {$time}! 👋 I'm Luna.\n\n" .
               "Ask me about:\n" .
               "💰 Gold price\n" .
               "📊 Market overview\n" .
               "📋 Purchase/Sale process\n" .
               "⛏️ Production\n" .
               "🪨 Geology\n" .
               "📈 Investment\n\n" .
               "What can I help you with? 😊";
    }

    protected function getHelp()
    {
        return "🤖 **What I Can Help With**\n\n" .
               "💰 **\"What's the gold price?\"**\n" .
               "📊 **\"Market overview\"**\n" .
               "📋 **\"How to purchase gold?\"**\n" .
               "⛏️ **\"Production status\"**\n" .
               "🪨 **\"Geology reports\"**\n" .
               "📈 **\"Investment outlook\"**\n\n" .
               "Just ask! 😊";
    }

    protected function getGeneralResponse($message)
    {
        // Check for keywords
        $keywords = ['gold', 'price', 'market', 'production', 'mining', 'geology', 'safety', 'investment', 'purchase', 'sale'];
        
        foreach ($keywords as $keyword) {
            if (strpos(strtolower($message), $keyword) !== false) {
                return "I can help you with **" . ucfirst($keyword) . "**! 🚀\n\n" .
                       "Just ask me specifically about it, or say \"help\" to see what I can do.\n\n" .
                       "What would you like to know? 😊";
            }
        }

        return "I'm not sure I understand that. 🤔\n\n" .
               "Try asking me about:\n" .
               "💰 Gold price\n" .
               "📊 Market overview\n" .
               "📋 Purchase/Sale process\n" .
               "⛏️ Production\n" .
               "🪨 Geology\n\n" .
               "What can I help with? 😊";
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    protected function getOrCreateConversation($user, $conversationId = null)
    {
        if ($conversationId) {
            $conversation = LunaConversation::where('user_id', $user->id)
                ->where('id', $conversationId)
                ->first();
            if ($conversation) return $conversation;
        }

        return LunaConversation::create([
            'user_id' => $user->id,
            'title' => 'New Conversation',
            'status' => 'active',
        ]);
    }

    protected function saveMessage($conversation, $user, $role, $content, $metadata = [])
    {
        return LunaMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user ? $user->id : null,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    protected function formatResponse($conversation, $response)
    {
        $messages = $conversation->messages()->get()->map(function ($msg) {
            return [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'created_at' => $msg->created_at ? $msg->created_at->toISOString() : null,
            ];
        });

        return [
            'success' => true,
            'conversation_id' => $conversation->id,
            'message' => $response,
            'messages' => $messages,
        ];
    }

    public function getConversation($userId, $conversationId)
    {
        return LunaConversation::where('user_id', $userId)
            ->where('id', $conversationId)
            ->with('messages')
            ->first();
    }

    public function getConversations($userId)
    {
        return LunaConversation::where('user_id', $userId)
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function deleteConversation($userId, $conversationId)
    {
        return LunaConversation::where('user_id', $userId)
            ->where('id', $conversationId)
            ->delete();
    }
}