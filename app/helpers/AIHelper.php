<?php
// FILE: /app/helpers/AIHelper.php

/**
 * SplashSupportAI - AI Helper
 * Abstraction layer for AI/LLM integration
 * Currently returns simulated responses - replace with real API calls
 */

class AIHelper {

    /**
     * Suggest reply for ticket
     *
     * @param int $tenantId
     * @param array $ticket
     * @param array $lastMessage
     * @param array $kbArticles
     * @return array
     */
    public static function suggestReply($tenantId, $ticket, $lastMessage, $kbArticles = array()) {
        // Track AI usage
        self::trackUsage($tenantId, 500); // Simulated token count

        // In production, this would call OpenAI/GPT API
        // For now, return simulated response

        $suggestions = array(
            array(
                'message' => "Thank you for contacting us regarding \"{$ticket['subject']}\". I understand your concern and I'm here to help.",
                'confidence' => 0.85,
                'source' => 'ai_generated'
            ),
            array(
                'message' => "I apologize for any inconvenience. Let me look into this issue for you right away.",
                'confidence' => 0.75,
                'source' => 'ai_generated'
            )
        );

        // If KB articles provided, add KB-based suggestion
        if (!empty($kbArticles)) {
            $suggestions[] = array(
                'message' => "Based on our knowledge base, this article might help: " . $kbArticles[0]['title'],
                'confidence' => 0.90,
                'source' => 'kb_article',
                'article_id' => $kbArticles[0]['id']
            );
        }

        return array(
            'suggestions' => $suggestions,
            'tokens_used' => 500
        );
    }

    /**
     * Auto reply to ticket (for simple/FAQ questions)
     */
    public static function autoReply($tenantId, $ticket, $lastMessage, $kbArticles = array()) {
        // Track AI usage
        self::trackUsage($tenantId, 600);

        // Simulate AI decision
        $confidence = 0.7 + (mt_rand(0, 25) / 100);

        // Only auto-reply if confidence is high enough
        if ($confidence < 0.85) {
            return array(
                'should_auto_reply' => false,
                'reason' => 'Confidence too low',
                'confidence' => $confidence
            );
        }

        $reply = "Thank you for your inquiry. Based on your question about \"{$ticket['subject']}\", ";

        if (!empty($kbArticles)) {
            $reply .= "I found this helpful article: {$kbArticles[0]['title']}. You can read more at " . BASE_URL . "/help/{$ticket['tenant_code']}/article/{$kbArticles[0]['slug']}. ";
        }

        $reply .= "If you need further assistance, our support team will follow up shortly.";

        return array(
            'should_auto_reply' => true,
            'message' => $reply,
            'confidence' => $confidence,
            'tokens_used' => 600
        );
    }

    /**
     * Summarize ticket conversation
     */
    public static function summarizeTicket($tenantId, $ticket, $messages) {
        self::trackUsage($tenantId, 400);

        // Simulate summary generation
        $summary = "Customer reported issue with {$ticket['subject']}. ";
        $summary .= "Ticket has " . count($messages) . " message(s). ";
        $summary .= "Current status: {$ticket['status']}.";

        return array(
            'summary' => $summary,
            'tokens_used' => 400
        );
    }

    /**
     * Classify ticket intent
     */
    public static function classifyIntent($tenantId, $ticket) {
        self::trackUsage($tenantId, 200);

        // Simulate intent classification
        $subject = strtolower($ticket['subject']);

        if (strpos($subject, 'billing') !== false || strpos($subject, 'invoice') !== false || strpos($subject, 'payment') !== false) {
            return array(
                'intent' => 'billing',
                'confidence' => 0.92,
                'suggested_team' => 'billing_team'
            );
        }

        if (strpos($subject, 'login') !== false || strpos($subject, 'password') !== false || strpos($subject, 'access') !== false) {
            return array(
                'intent' => 'technical_support',
                'confidence' => 0.88,
                'suggested_team' => 'technical_team'
            );
        }

        return array(
            'intent' => 'general_inquiry',
            'confidence' => 0.65,
            'suggested_team' => null
        );
    }

    /**
     * Search knowledge base using AI
     */
    public static function searchKnowledgeBase($tenantId, $query, $limit = 5) {
        self::trackUsage($tenantId, 300);

        // In production, use semantic search / embeddings
        // For now, simulate simple search

        try {
            $db = new Database();
            $sql = "SELECT id, title, slug, content_text
                    FROM kb_articles
                    WHERE tenant_id = :tenant_id
                    AND is_public = 1
                    AND published_at IS NOT NULL
                    AND (title LIKE :query OR content_text LIKE :query)
                    LIMIT :limit";

            $db->query($sql);
            $db->bind(':tenant_id', $tenantId);
            $db->bind(':query', '%' . $query . '%');
            $db->bind(':limit', $limit, PDO::PARAM_INT);

            $results = $db->all();

            return array(
                'results' => $results,
                'tokens_used' => 300
            );
        } catch (Exception $e) {
            return array(
                'results' => array(),
                'tokens_used' => 0
            );
        }
    }

    /**
     * Generate chatbot response
     */
    public static function chatbotReply($tenantId, $userMessage, $conversationHistory = array()) {
        self::trackUsage($tenantId, 500);

        // Simulate chatbot response
        $message = strtolower($userMessage);

        if (strpos($message, 'hello') !== false || strpos($message, 'hi') !== false) {
            $reply = "Hello! How can I help you today?";
        } elseif (strpos($message, 'hours') !== false || strpos($message, 'open') !== false) {
            $reply = "Our support team is available 24/7. How can I assist you?";
        } elseif (strpos($message, 'human') !== false || strpos($message, 'agent') !== false) {
            $reply = "I'll connect you with a human agent right away.";
        } else {
            $reply = "Thank you for your message. Let me help you with that.";
        }

        return array(
            'reply' => $reply,
            'should_escalate' => (strpos($message, 'human') !== false || strpos($message, 'agent') !== false),
            'tokens_used' => 500
        );
    }

    /**
     * Track AI token usage
     */
    private static function trackUsage($tenantId, $tokensUsed) {
        try {
            $db = new Database();
            $sql = "UPDATE tenant_usage
                    SET current_ai_tokens_used_month = current_ai_tokens_used_month + :tokens,
                        updated_at = NOW()
                    WHERE tenant_id = :tenant_id";

            $db->query($sql);
            $db->bind(':tokens', $tokensUsed);
            $db->bind(':tenant_id', $tenantId);
            $db->execute();
        } catch (Exception $e) {
            // Log error but don't throw - usage tracking shouldn't break AI features
            error_log("Failed to track AI usage: " . $e->getMessage());
        }
    }

    /**
     * Check if tenant has AI quota remaining
     */
    public static function checkQuota($tenantId) {
        try {
            $db = new Database();

            // Get tenant's subscription and usage
            $sql = "SELECT
                        ts.plan_id,
                        p.max_ai_tokens_per_month,
                        tu.current_ai_tokens_used_month
                    FROM tenant_subscriptions ts
                    JOIN plans p ON ts.plan_id = p.id
                    LEFT JOIN tenant_usage tu ON ts.tenant_id = tu.tenant_id
                    WHERE ts.tenant_id = :tenant_id
                    AND ts.status = 'active'
                    LIMIT 1";

            $db->query($sql);
            $db->bind(':tenant_id', $tenantId);
            $result = $db->single();

            if (!$result) {
                return array('allowed' => false, 'reason' => 'No active subscription');
            }

            $maxTokens = $result['max_ai_tokens_per_month'];
            $usedTokens = $result['current_ai_tokens_used_month'] ?? 0;

            // Null means unlimited
            if ($maxTokens === null) {
                return array('allowed' => true, 'remaining' => 'unlimited');
            }

            if ($usedTokens >= $maxTokens) {
                return array('allowed' => false, 'reason' => 'AI quota exceeded', 'limit' => $maxTokens);
            }

            return array('allowed' => true, 'remaining' => $maxTokens - $usedTokens);
        } catch (Exception $e) {
            return array('allowed' => false, 'reason' => 'Error checking quota');
        }
    }
}
