# Groq Setup Guide - Free AI Alternative

## Overview

**Groq** is a **100% free** AI inference platform with an extremely generous free tier. It's the **recommended alternative to OpenAI** for this application because:

✅ **Completely free** - No credit card required  
✅ **14,400 requests per day** on free tier  
✅ **Blazing fast** - Fastest inference speeds available  
✅ **OpenAI-compatible API** - Drop-in replacement  
✅ **No credit expiration** - Free tier doesn't expire  

## Free Tier Limits

Groq offers very generous free tier limits that are more than sufficient for this application:

| Metric | Free Tier |
|--------|-----------|
| Requests per minute | 30 |
| Requests per day | 14,400 (for llama-3.1-8b-instant) |
| Tokens per minute | 6,000 |
| Tokens per day | 500,000 |
| Cost | **$0** Forever |

**For this app:** Each AI post enhancement uses ~1 request and ~150-200 tokens. Users can generate **14,400 suggestions per day for free**!

> **Note:** Different models have different limits. The recommended `llama-3.1-8b-instant` has the highest daily request limit (14.4K/day).

## Step-by-Step Setup

### 1. Create a Groq Account

1. Go to **[Groq Console](https://console.groq.com)**
2. Click **"Sign Up"** or **"Get Started"**
3. Sign up with:
   - Google account
   - GitHub account
   - Or email/password

**No credit card required!**

### 2. Generate API Key

1. Once logged in, go to **[API Keys](https://console.groq.com/keys)**
2. Click **"Create API Key"**
3. Give it a name (e.g., "Pass The Ball App")
4. Copy the API key - it starts with `gsk_`
   ```
   gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```
5. **Save it securely** - you won't be able to see it again!

### 3. Configure Your Application

Open `.env` file and update these values:

```env
# Comment out or remove OpenAI settings
# OPENAI_API_KEY=sk-...
# OPENAI_ORGANIZATION=org-...

# Add Groq configuration
OPENAI_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
OPENAI_BASE_URI=https://api.groq.com/openai/v1
OPENAI_MODEL=llama-3.1-8b-instant
```

**Important:**
- Keep the variable name as `OPENAI_API_KEY` (the package expects this)
- The base URI tells it to use Groq instead of OpenAI
- The model determines which AI model to use

### 4. Restart Your Application

```bash
# Stop your dev server (Ctrl+C)
# Then restart it
composer dev
```

### 5. Test It!

1. Open your app at `http://127.0.0.1:8000`
2. Log in
3. Click "Create Post"
4. Type some text (minimum 5 characters)
5. Select a tone
6. Click **"AI Enhance"** ✨
7. You should see the AI-enhanced version!

## Available Models

Groq offers several models with different performance characteristics and rate limits. We recommend **llama-3.1-8b-instant** for the best balance of speed, quality, and limits.

### Recommended Models

| Model | RPM | RPD | TPM | TPD | Speed | Quality | Best For |
|-------|-----|-----|-----|-----|-------|---------|----------|
| `llama-3.1-8b-instant` | 30 | 14.4K | 6K | 500K | ⚡⚡⚡ | ⭐⭐⭐ | **Recommended** - Best daily limits |
| `llama-3.3-70b-versatile` | 30 | 1K | 12K | 100K | ⚡⚡ | ⭐⭐⭐⭐ | Higher quality, lower daily limit |
| `meta-llama/llama-4-scout-17b-16e-instruct` | 30 | 1K | 30K | 500K | ⚡⚡ | ⭐⭐⭐⭐ | Large token contexts |

### Other Available Models

| Model | RPM | RPD | TPM | TPD | Notes |
|-------|-----|-----|-----|-----|-------|
| `meta-llama/llama-guard-4-12b` | 30 | 14.4K | 15K | 500K | Content moderation |
| `meta-llama/llama-prompt-guard-2-86m` | 30 | 14.4K | 15K | 500K | Prompt injection detection |
| `qwen/qwen3-32b` | 60 | 1K | 6K | 500K | Alternative general-purpose model |
| `moonshotai/kimi-k2-instruct` | 60 | 1K | 10K | 300K | Kimi AI model |
| `openai/gpt-oss-120b` | 30 | 1K | 8K | 200K | Open-source GPT variant |
| `groq/compound` | 30 | 250 | 70K | ∞ | High token limits, low daily requests |
| `groq/compound-mini` | 30 | 250 | 70K | ∞ | Smaller compound variant |
| `allam-2-7b` | 30 | 7K | 6K | 500K | IBM's Allam model |

**Legend:**
- **RPM** = Requests Per Minute
- **RPD** = Requests Per Day  
- **TPM** = Tokens Per Minute
- **TPD** = Tokens Per Day

### Why llama-3.1-8b-instant?

✅ **Highest daily request limit** (14,400/day)  
✅ **Fast inference** (instant responses)  
✅ **Good quality** for social media content  
✅ **Sufficient token limits** for our use case  

For **14,400 free daily enhancements**, this is the clear winner!

To change models, update `OPENAI_MODEL` in your `.env` file.

## Troubleshooting

### "Invalid API Key" Error

**Problem:** API key not recognized

**Solutions:**
1. Check that your API key starts with `gsk_`
2. Verify you copied the entire key (no spaces)
3. Make sure you set `OPENAI_API_KEY` in `.env`
4. Restart your application after changing `.env`

### "Rate Limit Exceeded" Error

**Problem:** Too many requests in a short time

**Free Tier Limits:**
- **30 requests per minute** (all models)
- **Daily limits vary by model:**
  - `llama-3.1-8b-instant`: 14,400/day
  - `llama-3.3-70b-versatile`: 1,000/day
  - `groq/compound`: 250/day
  - Others: Check the table above

**Solutions:**
1. Wait 1 minute for RPM limit to reset
2. Wait 24 hours for daily limit to reset
3. Switch to a model with higher limits
4. If you need more, consider upgrading (still very affordable)

### "Model Not Found" Error

**Problem:** Invalid model name

**Solutions:**
1. Check the model name in `.env`
2. Use one of the supported models listed above
3. Make sure there are no typos

### Not Getting Enhanced Content

**Problem:** AI returns empty or errors

**Solutions:**
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify `OPENAI_BASE_URI` is set correctly
3. Make sure your API key is active
4. Test in Groq playground: https://console.groq.com/playground

## Cost Comparison

| Provider | Free Tier | After Free Tier |
|----------|-----------|-----------------|
| **Groq** | 14,400 req/day forever | $0.05-0.59 per 1M tokens |
| OpenAI | $5 credit (expires) | $0.15-60 per 1M tokens |
| Together AI | $25 credit (expires) | $0.20-1.20 per 1M tokens |

**Groq is the clear winner for free usage!**

## Advanced Configuration

### Using Multiple Models

You can switch models based on use case. Edit your `.env`:

```env
# For maximum daily requests (14.4K/day)
OPENAI_MODEL=llama-3.1-8b-instant

# For higher quality but fewer requests (1K/day)
OPENAI_MODEL=llama-3.3-70b-versatile

# For high token contexts (1K/day)
OPENAI_MODEL=meta-llama/llama-4-scout-17b-16e-instruct
```

You can also switch models programmatically in `app/Services/OpenAIService.php`:

```php
// Dynamic model selection based on tone
$model = match($tone) {
    'professional' => 'llama-3.3-70b-versatile', // Higher quality
    'humorous' => 'llama-3.1-8b-instant', // Faster
    default => config('openai.model'),
};
```

### Increasing Token Limits

For longer content, adjust in `app/Services/OpenAIService.php`:

```php
'max_tokens' => 150, // Increase from 100
```

### Custom Temperature

For more creative or consistent outputs:

```php
'temperature' => 0.5, // More consistent (0.0-1.0)
'temperature' => 0.9, // More creative (0.0-1.0)
```

## Monitoring Usage

1. Go to **[Groq Usage Dashboard](https://console.groq.com/usage)**
2. View:
   - Requests today
   - Tokens consumed
   - Rate limit status
3. Set up usage alerts (optional)

## Upgrading (Optional)

If you need more than the free tier:

1. Go to **[Groq Billing](https://console.groq.com/billing)**
2. Add a payment method
3. You'll only pay for usage above free tier
4. Very affordable: $0.05-0.59 per million tokens

**Most users will never need to upgrade!**

## Switching Back to OpenAI

If you want to switch back to OpenAI later:

```env
# Comment out Groq
# OPENAI_API_KEY=gsk_...
# OPENAI_BASE_URI=https://api.groq.com/openai/v1
# OPENAI_MODEL=llama-3.1-8b-instant

# Uncomment OpenAI
OPENAI_API_KEY=sk-...
OPENAI_ORGANIZATION=org-...
# OPENAI_BASE_URI= (leave empty for OpenAI)
# OPENAI_MODEL=gpt-4o-mini
```

Then restart your application.

## Additional Resources

- [Groq Console](https://console.groq.com)
- [Groq Documentation](https://console.groq.com/docs)
- [Groq API Reference](https://console.groq.com/docs/api-reference)
- [Groq Playground](https://console.groq.com/playground)
- [Model Performance Comparison](https://console.groq.com/docs/models)

## Support

If you encounter issues:

1. Check [Groq Status](https://status.groq.com)
2. Review [Groq Discord Community](https://discord.gg/groq)
3. Check `storage/logs/laravel.log` for errors
4. See main `docs/OPENAI_SETUP.md` for general troubleshooting

---

**🎉 You're all set!** Enjoy unlimited free AI-powered post enhancements with Groq!
