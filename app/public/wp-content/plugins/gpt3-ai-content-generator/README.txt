=== AI Puffer – Chat. Create. Automate. (formerly AI Power) ===
Contributors: senols
Tags: ai, chatbot, gpt, claude, openai
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.4.41
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Chat. Create. Automate.

== Description ==

**AI Puffer** is the **complete AI plugin for WordPress** — a full set of **artificial intelligence tools** to transform your site. From **AI chatbot** and **content generation** to **image creation, automation, and AI training** on your own data, AIP gives you everything in one place, right inside your WordPress dashboard.

Our **"Bring Your Own API Key"** model lets you connect to top AI providers (OpenAI, Google Gemini, Microsoft Azure, OpenRouter, DeepSeek, xAI and Ollama). No hidden credits — you use your own account and control your costs.

[📖 Documentation & Guides](https://docs.aipower.org/)  

### Why Choose AIP?

* **All-in-One** – Chatbot, AI Writer, AI Forms, Image Generator, Automation, WooCommerce AI tools, and more.
* **Train on Your Data** – Build your own **AI knowledge base** from posts, pages, products, PDFs, or files.
* **Voice + Chat** – Real-time voice agents and voice input for interactive AI experiences.
* **WooCommerce AI** – Generate product descriptions, titles, SEO tags, and sell AI credits to customers.
* **Fast & Flexible** – Works with OpenAI GPT-5/4o, Google Gemini & Imagen, Azure, Replicate, and others.
* **Secure** – 100% hosted on your WordPress site. Your data stays with you.

---

### 🚀 Key Features

#### 🤖 AI Chatbot
- Create custom **AI chatbots** for WordPress or any external site (embed with shortcode or HTML).
- Train bots on your **own website content** or external files.
- Enable **web search** (OpenAI or Google) for real-time answers.
- Add **voice input & playback**, triggers, and usage limits.

#### ✍️ AI Content Generator
- Generate **high-quality articles, blog posts, or product descriptions**.
- Input ideas via text, CSV, RSS feeds, or URLs.
- SEO-friendly output with custom templates, placeholders, and **Smart SEO** score improvement.

#### 📝 AI Forms
- Drag-and-drop **AI-powered forms** to process user input into useful outputs — from outlines to support replies.
- Connect forms to **web search**, uploaded files, image analysis, workflows, and your AI training data.

#### ⚙️ AI Automation Engine
- Schedule recurring or one-time AI tasks.
- Automate content creation, Smart SEO improvement, comment replies, or vector indexing.

#### 🎨 AI Image Generator
- Convert text to image with **OpenAI GPT Image, Google Imagen, and Replicate models**.
- Pull free stock images from **Pexels** or **Pixabay**.
- Works in posts, tasks, chatbot, and forms.

#### 📚 AI Training / Vector Database
- Build a **knowledge base** from your posts, products, PDFs, or uploaded files.
- Supports **OpenAI Vector Stores**, **Pinecone**, **Qdrant** and **Chroma**.
- Long content is chunked before embedding for safer external vector indexing.
- Use in Chatbot or Forms for **context-aware AI answers**.

#### 🛒 WooCommerce AI Tools
- Bulk-generate or enhance product descriptions, titles, and tags.
- Sell **AI credits** to customers via WooCommerce.

#### 🛠 Content Assistant
- Bulk-enhance existing posts, generate SEO titles/excerpts.
- Works in Block Editor, Classic Editor, or directly from the post list.

#### 🔌 REST API Access
- Call text, image, embedding, and chatbot functions programmatically from other apps.

---

== Installation ==

1. Install via Plugins → Add New, or upload to `/wp-content/plugins/gpt3-ai-content-generator`.
2. Activate via the **Plugins** menu.
3. Go to **AIP → Dashboard** and enter your API key for at least one provider (e.g., OpenAI).
4. Click **Sync Models** to load available AI models.
5. Explore modules (Chat, Write, Automate, etc.) and start using AI features.

---

== Frequently Asked Questions ==

= Do I need to buy credits from you? =  
No. AIP works with your **own API key** from AI providers like OpenAI, Google Gemini, etc. You pay them directly for usage.

= Which AI providers and models are supported? =  
We support **OpenAI** (GPT-5, GPT-4o, GPT-3.5, GPT Image, etc.), **Google** (Gemini, Imagen), **Microsoft Azure OpenAI**, **OpenRouter**, **DeepSeek**, **Ollama** and **Replicate**.

= Can I train the AI on my own content? =  
Yes. Use the **Train** module to index posts, pages, WooCommerce products, PDFs, or uploaded files into a **vector store**. Then link that knowledge base to your Chatbot or Forms.

= How do I limit AI usage for visitors or members? =  
The **Usage & Billing** tools let you set guest, user, or role-based usage limits for Chat, Forms, and Images. Limits can reset daily, weekly, monthly, or never.

= Can I monetize my AI tools? =  
Yes. Sell **credit packages** via WooCommerce. Credits are deducted when pricing rules apply to AI usage.

= What makes AIP different from other AI plugins? =  
AIP is **all-in-one** — instead of installing separate plugins for chatbots, content writing, AI forms, and WooCommerce AI, you get them all in one optimized toolkit with centralized settings.

= Is AIP compatible with GPT-5 and other latest models? =  
Yes. AIP supports GPT-5, GPT-4o, GPT-4 Turbo, Google Gemini 1.5, Imagen 4.0, and more.

---

== Screenshots ==

1. Main dashboard with quick access to all modules.
2. Add-ons page for enabling/disabling features.
3. Chatbot builder with real-time preview.
4. Content Writer with single, bulk, and RSS generation.
5. Automated Tasks scheduler.
6. Drag-and-drop AI Form builder.
7. AI Image Generator interface.
8. AI Training vector store management.
9. Usage & Billing system.
10. WooCommerce AI integration.

---

== Changelog ==

= 2.4.41 =

- Fixed a shortcode rendering issue in some WordPress setups.
- Fixed database table creation on servers with stricter MySQL/MariaDB index length limits.

= 2.4.39 =

- Fixed popup chatbot accessibility warnings caused by focusable controls inside hidden popup and hint containers.

= 2.4.38 =

Brought back PHP 7.4 support due to popular demand from PHP 7.4 fans.

= 2.4.37 =

- Code cleanup.

= 2.4.36 =

- Code cleanup.

= 2.4.35 =

- Fixed WordPress AI Connectors approval conflict that could block OpenAI vector-store indexing when the WordPress AI OpenAI connector plugin was active.
- Improved AI Puffer-managed WordPress AI connector status reporting in the WordPress AI dashboard.

= 2.4.34 =

- Added Claude Opus 4.8 to Anthropic recommended models.
- Improved Role Manager compatibility with custom roles from access management plugins.
- Improved admin styling isolation from other plugins.

= 2.4.33 =

Performance improvements.

= 2.4.32 =

- General bug fixes and improvements.

= 2.4.31 =

- General bug fixes and improvements.

= 2.4.29 =

- General bug fixes and improvements.

= 2.4.28 =

- Fixed a WordPress AI Client compatibility issue.
- Improved embedding batches.
- Improved Role Manager permissions for core modules, WordPress utilities, Usage, and Settings.

= 2.4.27 =

- Improved vector store list refresh and stale cache handling across OpenAI, Pinecone, Qdrant, and Chroma.
- Fixed the AI Forms OpenAI vector store selector in Knowledge Base settings.

= 2.4.26 =

- Improved webhook events.

= 2.4.25 =

- Added WordPress AI Connectors.

Read more: [WordPress AI Connectors](https://docs.aipower.org/wordpress-ai-connectors)

= 2.4.24 =

- Added WordPress 7.0 compatibility updates.
- Removed deprecated Google Gemini 3.1 Flash Lite Preview and added Gemini 3.5 Flash.
- Fixed long-content chunking for Pinecone, Qdrant, and Chroma so large WordPress posts can be embedded in safe chunks.
- Improved Qdrant strict-mode filters.
- Improved Chroma collection lookup/delete reliability.

= 2.4.23 =

DeepSeek api improvements.

= 2.4.22 =

Smart SEO improvements in Content Writer and Automated tasks.

= 2.4.21 =

I made two improvements for AI forms.

* Image upload: You can now upload images in AI Forms.
* Workflows: You can now connect multiple AI Forms, pass outputs and submitted answers from one form to the next, and build multi-step AI workflows.

Read more: [AI Forms Workflow](https://docs.aipower.org/ai-forms#workflow)

= 2.4.20 =

* Added DeepSeek V4 Flash and DeepSeek V4 Pro, and removed deprecated `deepseek-chat` / `deepseek-reasoner` aliases from model lists.
* Improved image generation in Content Writer and Automated Tasks with provider-specific options and expanded model support.

= 2.4.19 =

Added Smart SEO for Content Writer and Automated Tasks.

Smart SEO audits generated content against the active SEO plugin and can automatically improve its SEO score.

Learn more: https://docs.aipower.org/content-writer#smart-seo

= 2.4.18 =
* Improved performance for chatbots using OpenAI Vector Stores.
* Fixed frontend chatbot file uploads for OpenAI Vector Stores so uploaded files are only made available to chat after OpenAI finishes indexing them.

= 2.4.17 =
* Improved performance for chatbots using OpenAI Vector Stores.
* Fixed frontend chatbot file uploads for OpenAI Vector Stores so uploaded files are only made available to chat after OpenAI finishes indexing them.

= 2.4.16 =

Added xAI as a new provider.

Capabilities:

- Generate text in Chatbots, AI Forms, Content Writer, Automations, REST API, and bulk assistant flows through the xAI Responses API.
- Use xAI web search in Chatbots and AI Forms.
- Analyze uploaded images in Chatbots with xAI vision-capable Grok models.
- Generate images with xAI image models in Chatbots, Content Writer, Automations, and Image Generator.

More info: https://docs.aipower.org/ai-providers#xai

= 2.4.15 =

Added batch embedding support for Google.

More info: https://docs.aipower.org/knowledge-base#embedding-batches

= 2.4.14 =

General bug fixes and improvements in ai forms module.

= 2.4.13 =

New Vector integration: Chroma!

Check it's documentation here: https://docs.aipower.org/knowledge-base#chroma

= 2.4.12 =

- Improved Knowledge Base file uploads and chunk progress.

= 2.4.11 =

General bug fixes and improvements in Knowledge Base module.

= 2.4.10 =

General bug fixes and performance improvements.

= 2.4.9 =

- **Fixed**: Chatbot theme conflicts for some WordPress themes.
- **Fixed**: Content Writer search reset when switching optimize modes.
- **Changed**: PDF exports now use browser print for lighter downloads.

= 2.4.8 =

- **Added**: `gpt-image-2` is now the default OpenAI image model across Chatbot, Content Writer, AutoGPT, and Image Generator.
- **Deprecation**: Removed `dall-e-2` and `dall-e-3` from the plugin because OpenAI is deprecating them.

= 2.4.7 =

- **Added**: `gpt-image-2` is now the default OpenAI image model across Chatbot, Content Writer, AutoGPT, and Image Generator.
- **Deprecation**: Removed `dall-e-2` and `dall-e-3` from the plugin because OpenAI is deprecating them.

= 2.4.6 =

- Added a stop button with elapsed timer while chatbot responses are streaming.
- Improved chatbot popup UX: the launcher now hides while the popup is open, the popup takes the launcher position, desktop close behavior is clearer, and popup dragging is supported on desktop.
- Improved popup/mobile behavior with better viewport handling, safer fullscreen mobile presentation.

= 2.4.5 =

- You can now customize buy, purchase buttons in the chatbot, ai forms and image generator modules.
- Fixed an issue with external chatbot embedding.
- General bug fixes and performance improvements.

= 2.4.4 =

- Fixed an issue with Ollama model syncing.
- Fixed an issue with external chatbot embedding.
- General bug fixes and performance improvements.

= 2.4.3 =

- **Improved**: Automated tasks.
- **Fixed**: CSV parsing deprecation warning on newer PHP versions.
- General bug fixes and performance improvements.

= 2.4.2 =

- **Fixed**: WooCommerce issue.
- **Fixed**: Content Writer starter template reset.
- **Fixed**: AI Forms column width deprecation warning.

= 2.4.1 =

Bug fix.
