<?php
/**
 * AIHelper — Intelligent chatbot for LandSurvey Portal
 * Handles: greetings, FAQs, platform help, general knowledge,
 *          surveying topics, system navigation, and more.
 */
class AIHelper {

    private static $rules = [

        /* ── Greetings ── */
        'greeting' => [
            'keywords' => ['hello','hi','hey','good morning','good afternoon','good evening','good day','howdy','greetings','sup','wassup','yo','musta','kumusta'],
            'responses' => [
                "Hello! 👋 Welcome to **LandSurvey Portal**. I'm **Landbot AI** — your smart assistant. How can I help you today?",
                "Hi there! 😊 I'm **Landbot AI**. Ask me anything about land surveying, appointments, payments, engineers, or just chat!",
                "Hey! Great to see you. I'm here to help with all your land surveying needs on **LandSurvey Portal**. What can I do for you?",
                "Good day! I'm **Landbot AI**, your 24/7 assistant. Feel free to ask me anything about the system or land surveying!",
            ]
        ],

        /* ── How are you ── */
        'how_are_you' => [
            'keywords' => ['how are you','how r u','how do you do','are you okay','you good','how\'s it going','how are things','kamusta ka'],
            'responses' => [
                "I'm doing great, thanks for asking! 😄 Always ready to help. What can I do for you?",
                "Fantastic! I'm an AI so I never get tired — available 24/7 for you. What do you need?",
                "All systems running perfectly! 🤖 How about you? What can I help you with today?",
            ]
        ],

        /* ── Bot identity ── */
        'who_are_you' => [
            'keywords' => ['who are you','what are you','your name','are you a bot','are you ai','are you human','what is landbot','geobot','landbot'],
            'responses' => [
                "I'm **Landbot AI** 🤖 — the intelligent assistant for **LandSurvey Portal**. I can help you with:\n\n📅 Booking appointments\n👷 Finding engineers\n💰 Service pricing\n📍 Tracking surveys\n💳 Payment guidance\n🏢 Company info\n\nAsk me anything!",
                "I'm **Landbot AI**, a smart chatbot built for **LandSurvey Portal**. I know everything about the system and land surveying in the Philippines. What would you like to know?",
            ]
        ],

        /* ── Thank you ── */
        'thanks' => [
            'keywords' => ['thank you','thanks','thank u','ty','thx','appreciate','salamat','maraming salamat','thanks a lot'],
            'responses' => [
                "You're welcome! 😊 Is there anything else I can help you with?",
                "Happy to help! Let me know if you have more questions.",
                "Anytime! That's what I'm here for. 🙌",
                "Glad I could help! Feel free to ask anything else.",
            ]
        ],

        /* ── Goodbye ── */
        'goodbye' => [
            'keywords' => ['bye','goodbye','see you','take care','later','cya','good night','goodnight','paalam','ingat'],
            'responses' => [
                "Goodbye! 👋 Have a great day. Come back anytime you need help!",
                "See you later! Take care. 😊",
                "Bye! Don't hesitate to return if you have more questions. Have a wonderful day!",
            ]
        ],

        /* ── Booking ── */
        'booking' => [
            'keywords' => ['book','appointment','schedule','reserve','booking','set appointment','make appointment','how to book','paano mag-book'],
            'responses' => [
                "To book an appointment on **LandSurvey Portal**:\n\n1️⃣ Click **Book Appointment** in the sidebar\n2️⃣ Select a licensed engineer\n3️⃣ Choose your service type\n4️⃣ Pick a 🟢 green date on the calendar (green = available)\n5️⃣ Select a time slot\n6️⃣ Enter your location and notes\n7️⃣ Click **Confirm Booking**\n\nYou'll receive a confirmation code and email notification instantly!",
                "Booking is simple! Go to **Book Appointment** in your sidebar. Choose your engineer, service, and an available date (shown in green on the calendar). Our AI will suggest the best time slots based on the engineer's schedule.",
            ]
        ],

        /* ── Cancel / Reschedule ── */
        'cancel' => [
            'keywords' => ['cancel','reschedule','change date','change appointment','cancel appointment','move appointment'],
            'responses' => [
                "To cancel or reschedule an appointment:\n\n1. Go to **Track Status** in your sidebar\n2. Select the appointment\n3. Contact your engineer via **Messages**\n4. Request a cancellation or new date\n\n⚠️ Cancellations should be made at least **24 hours** before the scheduled date. The engineer will confirm the change.",
            ]
        ],

        /* ── Services ── */
        'services' => [
            'keywords' => ['service','survey type','what survey','boundary','topographic','construction layout','subdivision','geodetic','hydrographic','as-built','route survey','what do you offer'],
            'responses' => [
                "**LandSurvey Portal** offers 8 professional surveying services:\n\n📍 **Boundary Survey** — ₱5,000+ · 3–5 days\n🏔️ **Topographic Survey** — ₱8,000+ · 5–7 days\n🏗️ **Construction Layout** — ₱6,000+ · 2–3 days\n🏘️ **Subdivision Survey** — ₱15,000+ · 7–10 days\n🛣️ **Route Survey** — ₱12,000+ · 10–14 days\n🌊 **Hydrographic Survey** — ₱20,000+ · 14 days\n🌐 **Geodetic Survey** — ₱25,000+ · 21 days\n📐 **As-Built Survey** — ₱7,000+ · 4–6 days\n\nWhich service are you interested in?",
                "Our most popular services are **Boundary Survey** (for property titles) and **Topographic Survey** (for construction). All surveys are done by PRC-licensed geodetic engineers. Want details on a specific service?",
            ]
        ],

        /* ── Pricing ── */
        'pricing' => [
            'keywords' => ['price','cost','fee','rate','how much','pricing','charge','magkano','bayad','expensive','cheap','affordable','quote'],
            'responses' => [
                "Service pricing on **LandSurvey Portal**:\n\n• Boundary Survey — from ₱5,000\n• Topographic Survey — from ₱8,000\n• Construction Layout — from ₱6,000\n• Subdivision Survey — from ₱15,000\n• Route Survey — from ₱12,000\n• Hydrographic Survey — from ₱20,000\n• Geodetic Survey — from ₱25,000\n• As-Built Survey — from ₱7,000\n\nFinal pricing depends on project scope, area size, and location. Book an appointment for a detailed quote from your engineer!",
            ]
        ],

        /* ── Payment ── */
        'payment' => [
            'keywords' => ['payment','pay','gcash','bank transfer','credit card','paypal','cash','receipt','proof','bayad','how to pay','submit payment','payment method'],
            'responses' => [
                "**LandSurvey Portal** accepts multiple payment methods:\n\n💙 **GCash** — 0917-123-4567\n🏦 **Bank Transfer** — BDO Account 1234-5678-9012\n💳 **Credit Card** — via secure gateway\n💵 **Cash** — pay the engineer on survey day\n\nTo submit payment:\n1. Go to **Payments** in your sidebar\n2. Select your appointment\n3. Choose payment method\n4. Upload your receipt/screenshot\n5. Wait for admin verification (within 24 hours)\n\nYou'll be notified once verified!",
            ]
        ],

        /* ── Payment status ── */
        'payment_status' => [
            'keywords' => ['payment status','payment verified','payment pending','payment rejected','check payment','my payment'],
            'responses' => [
                "To check your payment status:\n\n1. Go to **Payments** in your sidebar\n2. View the status badge next to each payment:\n   • 🟡 **Pending** — waiting for admin review\n   • 🟢 **Verified** — payment confirmed ✅\n   • 🔴 **Rejected** — please resubmit with correct proof\n\nVerification usually takes within 24 hours. You'll receive a notification when it's done!",
            ]
        ],

        /* ── Engineers ── */
        'engineers' => [
            'keywords' => ['engineer','surveyor','licensed','prc','geodetic','find engineer','browse engineer','who are the engineers','best engineer','top engineer'],
            'responses' => [
                "All engineers on **LandSurvey Portal** are **PRC-licensed Geodetic Engineers**. Browse them in **Browse Engineers** — each profile shows:\n\n⭐ Star ratings & client reviews\n📋 Specialization & skills\n💼 Years of experience\n🏢 Company affiliation\n✅ Real-time availability status\n💰 Hourly rate\n\nYou can book directly from any engineer's profile!",
                "We have multiple licensed engineers across the Philippines. Go to **Browse Engineers** to filter by availability, specialization, or company. The **Recommended Engineers** section on your dashboard also shows top-rated engineers!",
            ]
        ],

        /* ── Track status ── */
        'status' => [
            'keywords' => ['status','track','progress','update','where is','when will','survey done','finished','complete','ongoing','track my survey'],
            'responses' => [
                "To track your survey in real-time:\n\n1. Go to **Track Status** in your sidebar\n2. Select your appointment\n3. View the live progress timeline\n\nYour engineer posts updates like:\n📍 Site inspection done\n📐 Boundary markers placed\n📋 Survey plan drafted\n✅ Final report ready\n\nYou'll receive a notification for every milestone!",
            ]
        ],

        /* ── Companies ── */
        'companies' => [
            'keywords' => ['company','companies','firm','office','partner','geotech','precisionmap','landmark','terrascan','northstar'],
            'responses' => [
                "**LandSurvey Portal** partners with 5 top surveying companies:\n\n🏢 **GeoTech Surveying Inc.** — Makati City\n🏢 **PrecisionMap Solutions** — Quezon City\n🏢 **LandMark Surveyors Co.** — Cebu City\n🏢 **TerraScan Philippines** — Davao City\n🏢 **NorthStar Survey Group** — Iloilo City\n\nGo to **Companies** in the sidebar to view their profiles, engineers, services, and Google Maps location!",
            ]
        ],

        /* ── Notifications ── */
        'notifications' => [
            'keywords' => ['notification','notify','alert','bell','reminder','email notification','notif'],
            'responses' => [
                "You'll receive notifications for:\n\n📅 Appointment confirmations & status changes\n💳 Payment verification results\n💬 New messages from engineers\n📋 Survey progress updates\n⭐ Feedback reminders\n\nNotifications appear in the 🔔 bell icon (top-right header) and as pop-up toasts. Manage email preferences in **Settings**.",
            ]
        ],

        /* ── Messages ── */
        'messages' => [
            'keywords' => ['message','chat','contact engineer','send message','inbox','talk to engineer','communicate','messaging'],
            'responses' => [
                "The **Messages** feature works like a professional chat app! You can:\n\n💬 Send real-time text messages\n📎 Attach files and images\n🎤 Send voice messages\n😊 Use emoji reactions\n📌 Pin important messages\n\nGo to **Messages** in the sidebar to start a conversation with any engineer. You can also message directly from an appointment's detail page!",
            ]
        ],

        /* ── Feedback ── */
        'feedback' => [
            'keywords' => ['feedback','review','rating','rate','stars','comment','testimonial','how to rate'],
            'responses' => [
                "After your survey is completed, leave a review in the **Feedback** section:\n\n⭐ Rate your engineer (1–5 stars)\n💬 Write a detailed comment about your experience\n\nYour feedback:\n✅ Helps other clients choose the right engineer\n✅ Helps engineers improve their service\n✅ Contributes to the engineer's overall rating\n\nGo to **Feedback** in your sidebar to submit!",
            ]
        ],

        /* ── Register ── */
        'register' => [
            'keywords' => ['register','sign up','create account','join','new account','how to register','paano mag-register'],
            'responses' => [
                "Creating an account on **LandSurvey Portal** is free!\n\n1. Click **Get Started** on the homepage\n2. Choose your role: **Client** or **Engineer**\n3. Fill in your name, email, and password\n4. Upload a profile photo (optional)\n5. Click **Create Account**\n\nYou'll be logged in immediately and can start booking surveys!",
            ]
        ],

        /* ── Login ── */
        'login' => [
            'keywords' => ['login','log in','sign in','forgot password','can\'t login','password reset','demo account','test account'],
            'responses' => [
                "To log in to **LandSurvey Portal**, go to the **Sign In** page and enter your email and password.\n\n**Demo accounts (password: 123456):**\n• 👤 Client: client@test.com\n• 👷 Engineer: engineer@test.com\n• 🛠️ Admin: admin@test.com\n\nForgot your password? Go to **Settings → Password** after logging in to change it.",
            ]
        ],

        /* ── Admin features ── */
        'admin' => [
            'keywords' => ['admin','administrator','admin dashboard','admin panel','manage users','manage engineers'],
            'responses' => [
                "The **Admin Dashboard** on LandSurvey Portal provides:\n\n📊 System overview (appointments, revenue, users)\n👥 User management (clients & engineers)\n💳 Payment verification\n📅 Schedule monitoring (view-only)\n⭐ Feedback & reviews overview\n🏢 Company management\n\nNote: Appointment confirmation is handled by engineers — admins have view-only access to appointments and schedules.",
            ]
        ],

        /* ── Engineer features ── */
        'engineer_features' => [
            'keywords' => ['engineer dashboard','engineer account','engineer features','what can engineer do','engineer portal'],
            'responses' => [
                "Engineers on **LandSurvey Portal** can:\n\n📅 View & manage assigned appointments\n✅ Accept or decline appointment requests\n🗓️ Set their availability schedule\n📋 Post survey progress updates\n💬 Message clients directly\n⭐ View client feedback & ratings\n👤 Manage their professional profile\n\nEngineers control their own schedule — clients see available slots in real-time!",
            ]
        ],

        /* ── Dark mode ── */
        'dark_mode' => [
            'keywords' => ['dark mode','light mode','theme','dark theme','night mode','dark'],
            'responses' => [
                "You can toggle **Dark Mode** anytime! Click the 🌙 moon icon in the top-right header. Your preference is saved automatically — it stays dark even after refreshing. The chatbot (that's me!) also switches to dark mode automatically! 😎",
            ]
        ],

        /* ── Language ── */
        'language' => [
            'keywords' => ['language','translate','filipino','tagalog','spanish','chinese','japanese','korean','arabic','lang','change language'],
            'responses' => [
                "Change the interface language using the 🌐 language selector in the top-right header. Available languages:\n\n🇵🇭 English / Filipino\n🇪🇸 Español\n🇨🇳 中文\n🇯🇵 日本語\n🇰🇷 한국어\n🇸🇦 العربية",
            ]
        ],

        /* ── What is land surveying ── */
        'what_is_survey' => [
            'keywords' => ['what is land survey','what is surveying','define survey','meaning of survey','what does surveyor do','ano ang survey'],
            'responses' => [
                "**Land surveying** is the science of precisely measuring and mapping the Earth's surface. Surveyors determine:\n\n📍 Property boundaries\n📐 Elevations and terrain contours\n🏗️ Construction positions and grades\n🗺️ Geographic coordinates\n📋 Legal lot descriptions\n\nIn the Philippines, land surveys are required for property titles, construction permits, and subdivision approvals. All surveys must be done by a **PRC-licensed Geodetic Engineer**.",
            ]
        ],

        /* ── PRC license ── */
        'prc' => [
            'keywords' => ['prc','license','licensed','geodetic engineer','board exam','prc license number','prc licensed'],
            'responses' => [
                "In the Philippines, land surveyors must be **PRC-licensed Geodetic Engineers**. The PRC (Professional Regulation Commission) issues licenses after passing the Geodetic Engineering board exam.\n\nAll engineers on **LandSurvey Portal** have verified PRC licenses — you can see their license number on their profile page. This ensures your survey is legally recognized!",
            ]
        ],

        /* ── Title transfer ── */
        'title' => [
            'keywords' => ['title','land title','transfer title','lot title','property title','tcl','ocl','deed of sale','title transfer'],
            'responses' => [
                "For **land title transfer** in the Philippines, you typically need:\n\n1. ✅ **Boundary Survey** — to verify lot boundaries\n2. 📋 Survey plan approved by DENR/LRA\n3. 📄 Deed of Sale\n4. 🏛️ BIR clearance and Capital Gains Tax payment\n5. 📝 Register of Deeds processing\n\nOur engineers handle the survey requirements and can guide you through the documentation. Book a **Boundary Survey** to get started!",
            ]
        ],

        /* ── Government agencies ── */
        'government' => [
            'keywords' => ['denr','lra','hlurb','namria','register of deeds','land registration','government','approval','bir'],
            'responses' => [
                "Key government agencies for land surveys in the Philippines:\n\n🏛️ **DENR** — approves survey plans\n🏛️ **LRA** — Land Registration Authority (title processing)\n🏛️ **NAMRIA** — National Mapping & Resource Info Authority\n🏛️ **HLURB/DHSUD** — subdivision approvals\n🏛️ **BIR** — tax clearance for title transfer\n\nOur engineers are accredited with these agencies and can handle all documentation requirements.",
            ]
        ],

        /* ── Technology ── */
        'technology' => [
            'keywords' => ['gps','drone','lidar','total station','autocad','technology','equipment','instrument','gnss'],
            'responses' => [
                "Our engineers use cutting-edge surveying technology:\n\n🛰️ **GPS/GNSS** — centimeter-level accuracy\n🚁 **Drone/UAV** — aerial mapping & photogrammetry\n📡 **LiDAR** — 3D point cloud scanning\n📏 **Total Station** — precise angle & distance measurement\n💻 **AutoCAD Civil 3D** — professional plan drafting\n📱 **GIS Software** — geographic data analysis\n\nThis ensures fast, accurate, and legally-recognized survey results!",
            ]
        ],

        /* ── Duration ── */
        'duration' => [
            'keywords' => ['how long','duration','days','weeks','time','finish','complete','turnaround','gaano katagal'],
            'responses' => [
                "Estimated survey completion times:\n\n• Boundary Survey — 3–5 days\n• Topographic Survey — 5–7 days\n• Construction Layout — 2–3 days\n• Subdivision Survey — 7–10 days\n• Route Survey — 10–14 days\n• Hydrographic Survey — 14 days\n• Geodetic Survey — 21 days\n• As-Built Survey — 4–6 days\n\nTimelines depend on project size, location, and complexity. Your engineer will confirm the exact schedule after reviewing your requirements.",
            ]
        ],

        /* ── Documents / Output ── */
        'documents' => [
            'keywords' => ['document','report','plan','output','result','deliverable','what will i get','certificate','survey plan'],
            'responses' => [
                "After your survey is completed, you'll receive:\n\n📋 **Survey Plan** — official DENR-approved plan\n📐 **Technical Description** — legal lot description\n📊 **Survey Report** — detailed findings and measurements\n💾 **Digital Files** — AutoCAD/PDF formats\n📸 **Site Photos** — field documentation photos\n\nAll documents are signed and sealed by your licensed engineer and are legally recognized by government agencies.",
            ]
        ],

        /* ── Support ── */
        'support' => [
            'keywords' => ['support','help','contact','reach','call','email','phone','office','customer service','hotline','tulong'],
            'responses' => [
                "Need help? Here's how to reach **LandSurvey Portal** support:\n\n📞 **Phone:** +63 82 234 5678\n📧 **Email:** info@landsurveyportal.ph\n💬 **Messages:** Use the Messages feature in your dashboard\n🕐 **Hours:** Monday–Saturday, 8:00 AM – 5:00 PM\n\nOr keep chatting with me — I'm available 24/7! 😊",
            ]
        ],

        /* ── Recommended engineers ── */
        'recommended' => [
            'keywords' => ['recommended','best engineer','top rated','highest rated','who should i choose','suggest engineer','recommend'],
            'responses' => [
                "Check the **Recommended Engineers** section at the bottom of your dashboard! It shows our top-rated engineers based on:\n\n⭐ Overall star rating\n💬 Number of client reviews\n✅ Completion rate\n🏆 Years of experience\n\nYou can also go to **Browse Engineers** to filter by specialization, availability, and company. Click any engineer to view their full profile and book directly!",
            ]
        ],

        /* ── Profile ── */
        'profile' => [
            'keywords' => ['profile','my profile','edit profile','update profile','change photo','change name','account info'],
            'responses' => [
                "To update your profile on **LandSurvey Portal**:\n\n1. Click your name/photo in the top-right header\n2. Select **My Profile**\n3. Edit your name, phone, address, and bio\n4. Upload a new profile photo\n5. Click **Save Changes**\n\nFor engineers, you can also update your specialization, license number, skills, and hourly rate from your profile page.",
            ]
        ],

        /* ── Settings ── */
        'settings' => [
            'keywords' => ['settings','setting','password','change password','notification settings','preferences'],
            'responses' => [
                "Access your settings from the sidebar or the top-right user menu. In **Settings** you can:\n\n🔒 Change your password\n🔔 Manage notification preferences\n🌐 Update contact information\n\nFor admins, Settings also includes system configuration options like site name, maintenance mode, and registration controls.",
            ]
        ],

        /* ── Math / Area ── */
        'math' => [
            'keywords' => ['calculate','area','hectare','square meter','sqm','lot size','compute','measurement','convert'],
            'responses' => [
                "Useful land area conversions:\n\n• 1 hectare = 10,000 sq meters\n• 1 sq meter = 0.0001 hectares\n• 1 acre = 4,047 sq meters ≈ 0.4047 hectares\n• 1 square kilometer = 100 hectares\n\nFor precise and legally-recognized lot area computation, our engineers use GPS and total station measurements. Book a survey for an official result!",
            ]
        ],

        /* ── Weather ── */
        'weather' => [
            'keywords' => ['weather','rain','typhoon','storm','sunny','climate','forecast','ulan','bagyo'],
            'responses' => [
                "Weather can affect survey schedules! Heavy rain, typhoons, or poor visibility may cause rescheduling. Your engineer will notify you via **Messages** if weather conditions require a date change.\n\n☀️ Best survey season: **November–May** (dry season)\n🌧️ Rainy season: **June–October** — surveys may be delayed\n\nWe recommend booking morning slots during dry season for best results!",
            ]
        ],

        /* ── Philippines ── */
        'philippines' => [
            'keywords' => ['philippines','pilipinas','manila','cebu','davao','luzon','visayas','mindanao','metro manila','iloilo'],
            'responses' => [
                "**LandSurvey Portal** serves clients across the Philippines! We have engineers in:\n\n🏙️ Metro Manila (Makati, BGC, Quezon City, Pasig)\n🌴 Cebu City\n🌺 Davao City\n🏝️ Iloilo City\n\nAnd expanding to more areas. Check the **Companies** section to find engineers near you, or browse all engineers in **Browse Engineers**!",
            ]
        ],

        /* ── Compliment ── */
        'compliment' => [
            'keywords' => ['great','awesome','amazing','excellent','good job','well done','nice','perfect','love it','fantastic','magaling'],
            'responses' => [
                "Thank you so much! 😊 We work hard to make **LandSurvey Portal** the best it can be. Is there anything else I can help you with?",
                "That means a lot! 🙏 We're always improving. Let me know if you need anything else!",
            ]
        ],

        /* ── Complaint ── */
        'complaint' => [
            'keywords' => ['problem','issue','complaint','not working','error','bug','wrong','bad','terrible','disappointed','frustrated','hindi gumagana'],
            'responses' => [
                "I'm sorry to hear you're having trouble! 😔 Here's what you can do:\n\n1. **Refresh the page** and try again\n2. **Clear your browser cache** (Ctrl+Shift+Delete)\n3. **Contact support** via Messages or email info@landsurveyportal.ph\n4. **Call us** at +63 2 8123 4567\n\nWe'll resolve your issue as quickly as possible!",
            ]
        ],

        /* ── Joke ── */
        'joke' => [
            'keywords' => ['joke','funny','laugh','humor','tell me a joke','make me laugh','biro'],
            'responses' => [
                "Why did the surveyor break up with the GPS? Because it kept saying 'recalculating'! 😄\n\nNeed help with something else?",
                "Why do surveyors make great friends? Because they always know where you stand! 📍😂",
                "What did the boundary marker say to the property owner? 'I've got you covered!' 😄",
                "Why did the geodetic engineer bring a ladder to work? Because they heard the survey was on a higher level! 🏔️😂",
            ]
        ],

        /* ── Default fallback ── */
        'default' => [
            'keywords' => [],
            'responses' => [
                "I'm not sure I fully understood that, but I'm here to help! 😊 You can ask me about:\n\n📅 Booking appointments\n💰 Service pricing\n👷 Finding engineers\n📍 Tracking your survey\n💳 Payment methods\n🏢 Partner companies\n⭐ Feedback & reviews\n\nWhat would you like to know?",
                "Hmm, I didn't quite catch that. Could you rephrase? I can help with appointments, services, payments, engineer info, and general surveying questions on **LandSurvey Portal**!",
                "I'm still learning! 🤖 For that specific question, please contact our support team at info@landsurveyportal.ph or use the **Messages** feature to chat with our team directly.",
                "Great question! For the most accurate answer, I'd recommend reaching out to our support team. In the meantime, is there anything about our surveying services I can help with?",
            ]
        ],
    ];

    public static function generateResponse(string $message): string {
        $msg = mb_strtolower(trim($message));
        if (empty($msg)) {
            return "Please type a message and I'll do my best to help! 😊";
        }
        foreach (self::$rules as $key => $rule) {
            if ($key === 'default') continue;
            if (self::matches($msg, $rule['keywords'])) {
                return self::pick($rule['responses']);
            }
        }
        return self::pick(self::$rules['default']['responses']);
    }

    public static function suggestSlots(string $service_type, array $available_slots): string {
        if (empty($available_slots)) {
            return "I couldn't find available slots for the selected date. Please try a different date — green dates on the calendar have open slots!";
        }
        $suggestions = [];
        foreach (array_slice($available_slots, 0, 3) as $slot) {
            $suggestions[] = date('h:i A', strtotime($slot['start_time'])) . ' – ' . date('h:i A', strtotime($slot['end_time']));
        }
        $tips = [
            'Boundary Survey'     => 'Morning slots (8AM–12PM) are best for boundary surveys — better light and fewer distractions.',
            'Topographic Survey'  => 'Full-day slots are recommended for topographic surveys to ensure complete coverage.',
            'Construction Layout' => 'Early morning slots avoid site traffic and give the engineer maximum working time.',
            'Geodetic Survey'     => 'Full-day slots are ideal for geodetic surveys requiring extensive measurements.',
            'default'             => 'Morning slots generally offer the best conditions for accurate surveying work.',
        ];
        $tip = $tips[$service_type] ?? $tips['default'];
        return $tip . "\n\nAvailable slots:\n• " . implode("\n• ", $suggestions);
    }

    private static function matches(string $msg, array $keywords): bool {
        foreach ($keywords as $kw) {
            if (mb_strpos($msg, $kw) !== false) return true;
        }
        return false;
    }

    private static function pick(array $responses): string {
        return $responses[array_rand($responses)];
    }
}
