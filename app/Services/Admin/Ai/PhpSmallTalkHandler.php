<?php

namespace App\Services\Admin\Ai;

/**
 * Layer 1 — PHP Small Talk Handler
 *
 * Intercepts greetings, thanks, farewells, and acknowledgements
 * before they reach Gemini. Returns a PHP-generated reply or null.
 *
 * Detection strategy:
 *   1. Normalize message (lowercase, strip punctuation)
 *   2. If ANY ERP-domain keyword found → NOT small talk → return null
 *   3. If message > 150 chars with no ERP signals → unknown question → return null
 *   4. Match against small talk phrase lists → return static reply
 *
 * Zero Gemini calls. Zero DB queries. Pure PHP.
 * Multi-language support intentionally deferred — English only for now.
 */
class PhpSmallTalkHandler
{
    // ─────────────────────────────────────────────────────────────────────────
    // ERP SIGNAL BLOCKLIST
    // If ANY of these appear in the message → NOT small talk → return null.
    // Words are specific enough to business domain to avoid false positives.
    // ─────────────────────────────────────────────────────────────────────────

    private const ERP_SIGNALS = [
        // Billing & invoicing
        'invoice', 'invoices', 'bill', 'bills', 'billing', 'receipt', 'receipts',
        // Orders & commerce
        'order', 'orders', 'sale', 'sales', 'selling', 'revenue', 'income',
        // Expenses
        'expense', 'expenses', 'kharcha', 'kharche', 'kharchi', 'kharch',
        // Purchases & procurement
        'purchase', 'purchases', 'kharidi', 'kharidari', 'procurement',
        // Inventory & products
        'product', 'products', 'item', 'items', 'stock', 'inventory',
        'barcode', 'sku', 'warehouse',
        // Business contacts
        'customer', 'customers', 'client', 'clients', 'grahak',
        'supplier', 'suppliers', 'vendor', 'vendors',
        // Finance
        'payment', 'payments', 'paisa', 'raqam', 'amount', 'balance',
        'profit', 'loss', 'earning', 'earnings',
        // HR & attendance
        'attendance', 'hajiri', 'haajri', 'absent',
        'employee', 'employees', 'karmachari', 'staff',
        // CRM
        'lead', 'leads', 'crm', 'followup', 'follow-up', 'follow up',
        // Business documents
        'quotation', 'quotations', 'quote', 'quotes',
        'challan', 'challans', 'challan',
        'report', 'reports', 'ledger', 'summary',
        // Status keywords (business context)
        'pending', 'overdue', 'unpaid', 'draft', 'due',
        // Hindi/Hinglish business terms
        'bikri', 'bechna', 'vyapaar',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // SMALL TALK PHRASE LISTS  (English only — multilingual deferred)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Greetings — social openers.
     * Single words AND multi-word phrases, ordered most-specific first.
     */
    private const GREETINGS = [
        // Time-of-day greetings (multi-word first to avoid partial match issues)
        'good morning', 'good evening', 'good afternoon', 'good day',
        // Casual multi-word
        'hey there', 'hello there', 'hi there',
        // Standard single-word
        'hello', 'helo', 'helloo', 'helloooo',
        'hi', 'hii', 'hiii', 'hiiii', 'hiiiii',
        'hey', 'heyy', 'heyyy', 'heya', 'heyya',
        'howdy', 'hola', 'yo', 'yoo', 'yooo', 'ayo',
        // Short codes
        'gm', 'gd',
        // Indian greetings (Romanised, commonly typed in English)
        'namaste', 'namaskar', 'namasste', 'namaskaar',
        'jai shree krishna', 'jai jinendra', 'radhe radhe',
        'jai mata di', 'sat sri akal',
        'salam', 'salaam', 'adaab',
        'suprabhat', 'shubh prabhat',
    ];

    /**
     * "How are you" / "what's up" style queries — social check-ins.
     */
    private const HOW_ARE_YOU = [
        // Phrases (multi-word, check first)
        'how are you doing', 'how are you today',
        'how are you', 'how r you', 'how are u', 'how r u', 'how ru',
        "how's it going", "how's it", 'how is it going', 'how goes it',
        'how is life', 'how is everything', "how's everything",
        'how is it', "what's up", 'whats up', 'wassup', 'wazzup',
        "what's good", 'what is up',
        'you there', 'are you there', 'anybody there', 'anyone there',
        'you alive', "you're alive", 'is this working', 'hello are you there',
        // Single-word socials
        'wassap', 'wsup', 'sup',
        // Indian/Hinglish (Romanised)
        'kaisa hai', 'kaisi ho', 'kaise ho', 'kaise hain',
        'kya haal hai', 'kya haal', 'kya chal raha hai', 'kya chal rha hai',
        'kya scene hai', 'kya scene', 'sab theek', 'sab thik',
        'kem cho', 'kem chho',
        'all good', 'all well',
    ];

    /**
     * Thank you / appreciation messages.
     */
    private const THANKS = [
        // Standard thanks (multi-word first)
        'thank you so much', 'thank you very much', 'thank you bhai',
        'thank you', 'thankyou', 'thank u', 'thank yu',
        'thanks a lot', 'thanks so much', 'thanks bhai', 'thanks yaar',
        'thanks', 'thnx', 'thx', 'ty', 'tysm', 'tyvm',
        'many thanks', 'much appreciated', 'highly appreciated',
        'appreciate it', 'appreciate that', 'appreciated', 'great thanks',
        'got it thank you', 'got it thanks', 'understood thanks',
        // Praise / approval
        'well done', 'good job', 'nice work', 'great job', 'nice one',
        'that helped', 'very helpful', 'so helpful', 'super helpful',
        'great', 'awesome', 'perfect', 'excellent', 'superb', 'amazing',
        'brilliant', 'fantastic', 'wonderful',
        'cool', 'nice', 'wow',
        // Hinglish/Hindi (Romanised)
        'dhanyawad', 'dhanyavaad', 'dhanyavad',
        'shukriya', 'shukriyaa', 'bahut shukriya',
        'bahut dhanyawad', 'meherbani', 'bahut meherbani',
        'waah', 'wah', 'waaah', 'shabash', 'bahut badhiya', 'badhiya',
        'ekdum sahi', 'maja aa gaya', 'maja aya',
    ];

    /**
     * Goodbye / farewell messages.
     */
    private const FAREWELLS = [
        // Multi-word first
        'good night', 'good bye',
        'see you later', 'see you soon', 'see ya later',
        'catch you later', 'catch ya later',
        'take care', 'ok bye', 'okay bye',
        'talk to you later', 'talk later',
        'logging off', 'signing off', 'signing out',
        'gotta go', 'have to go', 'going now',
        // Single / short
        'goodbye', 'goodnight', 'bye', 'bbye', 'byee', 'byeee', 'byeeee',
        'byebye', 'bye bye',
        'see you', 'see ya', 'cya',
        'later', 'ttyl', 'ttys', 'gtg', 'gn',
        // Indian farewells (Romanised)
        'alvida', 'phir milenge', 'phir milte hain', 'phir milte hai',
        'ok bye bhai', 'chal bye', 'chal bhai',
        'aavjo',
    ];

    /**
     * Single-word / short acknowledgements — "ok", "yes", "got it", etc.
     * These use exact/word-boundary matching to avoid false hits inside
     * longer ERP words (e.g. "ok" inside "stock", "k" inside "kharcha").
     */
    private const ACKNOWLEDGEMENTS = [
        // Multi-word acknowledgements (str_contains safe)
        'got it', 'i got it', 'got this', 'understood that',
        'makes sense', 'that makes sense', 'i see', 'i understand',
        'no problem', 'no worries', 'all good now',
        // Single/short (use word-boundary matching — see detectType())
        'ok', 'okay', 'okk', 'okkk', 'okk bhai', 'ohk',
        'k', 'kk', 'kkk',
        'alright', 'aight',
        'noted', 'understood', 'fine',
        'sure', 'sure thing',
        'yep', 'yup', 'yeah', 'yea', 'ya', 'yes', 'yess', 'yesss',
        'no', 'nope', 'nah',
        'hmm', 'hm', 'hmmm',
        'acha', 'accha', 'achha', 'thik hai', 'theek hai', 'sahi hai',
        'haan', 'ha', 'bilkul', 'pakka',
    ];

    /**
     * Identity queries regarding name.
     */
    private const IDENTITY_NAME = [
        'what is your name', 'whats your name', 'your name', 'tell me your name',
        'who are you', 'what are you called', 'naam kya hai', 'tamaru naam shu',
    ];

    /**
     * Identity queries regarding developers/creators.
     */
    private const IDENTITY_CREATOR = [
        'who developed you', 'who made you', 'who built you', 'who created you',
        'who is your creator', 'who is your developer', 'kisne banaya', 'kone banavyu',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // RESPONSE TEMPLATES  (English only)
    // Multiple variants per type → random pick to feel less robotic.
    // ─────────────────────────────────────────────────────────────────────────

    private const RESPONSES = [
        'greeting' => [
            'Hello! How can I help you today?',
            'Hi there! What business data can I fetch for you?',
            'Hey! What would you like to know?',
            'Hello! Ask me anything about your business data.',
        ],
        'how_are_you' => [
            "I'm doing great, thanks for asking! What can I help you with?",
            "All good here! What business data do you need?",
            "Ready to help! Ask me about your sales, orders, expenses, or anything else.",
        ],
        'thanks' => [
            "You're welcome! Let me know if you need anything else.",
            "Happy to help! Feel free to ask anytime.",
            "Glad I could assist! What else can I do for you?",
            "Of course! Ask me anything about your business.",
        ],
        'farewell' => [
            'Goodbye! Have a great day!',
            'See you later! Good luck with your business.',
            'Take care! Come back whenever you need help.',
            'Bye! Feel free to return anytime.',
        ],
        'acknowledgement' => [
            "Got it! Let me know if you need anything.",
            "Sure! What else can I help you with?",
            "Alright! Feel free to ask anything.",
            "Of course! What would you like to know?",
        ],
        'identity_name' => [
            "I am Plantiq AI assistant, made by Qlinkon! How can I help you manage your business updates today?",
            "I'm Plantiq AI assistant, your smart digital business companion developed by Qlinkon.",
        ],
        'identity_creator' => [
            "I was developed by the expert tech team at Qlinkon to automate and track your business insights.",
            "I am proudly built and powered by Qlinkon!",
        ],
        'gibberish' => [
            "Please ask a valid business question about your data.",
            "I didn't quite catch that. Could you type a complete word or sentence?",
            "Can you please clarify? I can help you with sales, expenses, and other business reports.",
        ],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Attempt to handle the message as small talk.
     *
     * @param  string  $message   Raw user message
     * @param  string  $language  en|hi|gu|hinglish (language support deferred)
     * @return string|null        Reply string, or null if not small talk
     */
    public static function handle(string $message, string $language = 'en'): ?string
    {
        $normalized = self::normalize($message);

        // Gate 0: Empty message after normalization
        if ($normalized === '') {
            return "Please type a message.";
        }

        // Gate 1: ERP signals present → business query, not small talk
        if (self::hasErpSignal($normalized)) {
            return null;
        }

        // Gate 2: Detect Known Small Talk (Greetings, Thanks, etc.)
        // Doing this BEFORE Gibberish check protects valid short words like "ok", "k", "hmm"
        $type = self::detectType($normalized);
        if ($type !== null) {
            return self::pickResponse($type);
        }

        // Gate 3: Gibberish / Noise detection → block spam/masti to save tokens
        if (self::isGibberish($normalized)) {
            return self::pickResponse('gibberish');
        }

        // Gate 4: Very long message with no ERP signals
        if (mb_strlen($normalized) > 150) {
            return null;
        }

        // Not small talk, not gibberish → continue to Layer 2 (PHP) / Layer 3 (Gemini)
        return null; 
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lowercase, strip punctuation/symbols, collapse whitespace.
     * Works correctly with multibyte (Hindi/Gujarati) characters.
     */
    private static function normalize(string $message): string
    {
        $m = mb_strtolower(trim($message));
        // Remove anything that is not a Unicode letter, digit, or whitespace
        $m = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $m);
        // Collapse multiple spaces into one
        $m = preg_replace('/\s+/', ' ', $m);
        return trim($m);
    }

    /**
     * Returns true if any ERP-domain keyword is found in the normalized message.
     * Uses str_contains (substring check) which is intentionally loose:
     *   - A false positive (ERP term inside a social phrase) → passes to Gemini safely.
     *   - A false negative (social phrase containing no ERP terms) → rare in practice.
     * We accept the conservative tradeoff: never silently drop ERP queries.
     */
    private static function hasErpSignal(string $normalized): bool
    {
        foreach (self::ERP_SIGNALS as $signal) {
            if (str_contains($normalized, $signal)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect which category of small talk this message belongs to.
     *
     * Priority order matters:
     *   farewells  > thanks > how_are_you > greetings > acknowledgements
     *
     * Farewells are checked first because "good night" contains "good"
     * which also appears in "good morning" (a greeting).
     * Thanks is checked before greetings to catch "great" / "wow" correctly.
     *
     * Acknowledgements use stricter boundary matching to avoid false hits
     * on short strings like "k" or "ok" appearing inside other words.
     */
    /**
     * Identifies noise, keyboard mashing, or extremely short nonsense strings
     * to prevent wasteful AI token consumption.
     */
    private static function isGibberish(string $normalized): bool
    {
        $noSpace = str_replace(' ', '', $normalized);
        $len = mb_strlen($noSpace);

        if ($len === 0) return true;

        // 1. Overall very short message (<= 2 chars excluding spaces).
        // Since we check detectType() first, any 1-2 char message reaching here
        // wasn't caught as a valid word (like 'ok', 'hi') so it must be garbage (like 'd', 'x', 'ii').
        if ($len <= 2) {
            return true;
        }

        // 2. Word-by-word analysis. If EVERY word is nonsense, block the whole message.
        $words = array_filter(explode(' ', $normalized));
        $nonsenseWords = 0;

        foreach ($words as $word) {
            $wordLen = mb_strlen($word);

            // Rule A: 1-2 char word that is not a number (e.g., "ii", "bb", "z")
            if ($wordLen <= 2 && !is_numeric($word)) {
                $nonsenseWords++;
                continue;
            }

            // Rule B: Word is all consonants (no vowels/y) and >= 3 chars (e.g., "zxcv", "dfgh")
            if ($wordLen >= 3 && preg_match('/^[a-z]+$/', $word) && !preg_match('/[aeiouy]/', $word)) {
                $nonsenseWords++;
                continue;
            }

            // Rule C: Word is literally just the exact same letter repeated 3+ times (e.g., "uuu", "bbbb")
            if (preg_match('/^(.)\1{2,}$/u', $word)) {
                $nonsenseWords++;
                continue;
            }
        }

        // If ALL words in the message are classified as nonsense (e.g. "ii bb"), block it!
        if (count($words) > 0 && $nonsenseWords === count($words)) {
            return true;
        }

        return false;
    }

    private static function detectType(string $normalized): ?string
    {
        // ── Identity Checks ──
        foreach (self::IDENTITY_NAME as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return 'identity_name';
            }
        }

        foreach (self::IDENTITY_CREATOR as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return 'identity_creator';
            }
        }

        // ── Farewells (check before greetings — "good night" shares "good") ──
        foreach (self::FAREWELLS as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return 'farewell';
            }
        }

        // ── Thanks / appreciation ──
        foreach (self::THANKS as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return 'thanks';
            }
        }

        // ── How are you / social check-ins ──
        foreach (self::HOW_ARE_YOU as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return 'how_are_you';
            }
        }

        // ── Greetings ──
        foreach (self::GREETINGS as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return 'greeting';
            }
        }

        // ── Acknowledgements — stricter matching ──
        // Multi-word acks (e.g. "got it", "makes sense") use str_contains.
        // Single/short acks (e.g. "ok", "k", "yes") use word-boundary logic
        // so we don't match "ok" inside "stock" or "k" inside "kharcha".
        foreach (self::ACKNOWLEDGEMENTS as $phrase) {
            if (str_word_count($phrase) > 1 || mb_strlen($phrase) > 4) {
                // Multi-word or longer phrase → substring match is safe
                if (str_contains($normalized, $phrase)) {
                    return 'acknowledgement';
                }
            } else {
                // Short single-word → only match if it IS the whole message,
                // or appears at start/end/between spaces (word boundary simulation)
                if (
                    $normalized === $phrase
                    || str_starts_with($normalized, $phrase . ' ')
                    || str_ends_with($normalized, ' ' . $phrase)
                    || str_contains($normalized, ' ' . $phrase . ' ')
                ) {
                    return 'acknowledgement';
                }
            }
        }

        return null;
    }

    /**
     * Pick a random response variant for the detected type.
     * Randomness avoids repetitive robotic answers.
     */
    private static function pickResponse(string $type): string
    {
        $variants = self::RESPONSES[$type] ?? ["How can I help you?"];
        return $variants[array_rand($variants)];
    }
}