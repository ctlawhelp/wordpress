# Cricket Setup Guide

How to replicate this AI assistant setup from scratch. Written by Cricket, based on the actual setup process.

---

## Overview

This setup gives you a persistent AI assistant (Cricket) that:
- Lives in a Discord server
- Has memory between sessions
- Can connect to external services (Google, Mailchimp, GitHub, etc.)
- Runs 24/7 on a VPS

---

## What You Need

### Accounts to create (before you start)
- [ ] VPS provider account (DigitalOcean, Vultr, Linode, or Hetzner recommended)
- [ ] Anthropic account → API key (console.anthropic.com)
- [ ] Discord developer account (discord.com/developers)
- [ ] GitHub account (for storing workspace files)

---

## Step 1: Get a VPS

**Recommended specs:**
- Ubuntu 22.04 LTS
- 1–2 GB RAM
- 25 GB storage
- Any major provider works

**After provisioning:**
```bash
# Update the system
sudo apt update && sudo apt upgrade -y

# Install Node.js (v18+)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify
node --version
npm --version
```

---

## Step 2: Install OpenClaw

```bash
npm install -g openclaw
openclaw --version
```

---

## Step 3: Create a Discord Bot

1. Go to https://discord.com/developers/applications
2. Click **"New Application"** → name it "Cricket" (or your preferred name)
3. Go to **Bot** tab → click **"Add Bot"**
4. Copy the **Bot Token** (keep this secret)
5. Under **Privileged Gateway Intents**, enable:
   - Message Content Intent
   - Server Members Intent
6. Go to **OAuth2 → URL Generator**:
   - Scopes: `bot`
   - Bot permissions: `Send Messages`, `Read Messages/View Channels`, `Add Reactions`
7. Copy the generated URL → open it → invite the bot to your Discord server

---

## Step 4: Configure OpenClaw

```bash
openclaw init
```

You'll be prompted for:
- **Anthropic API key** → from console.anthropic.com
- **Discord bot token** → from Step 3
- **Discord channel ID** → right-click your channel in Discord → Copy Channel ID

The config file lives at `~/.openclaw/config.json`.

---

## Step 5: Set Up Your Workspace

```bash
mkdir -p ~/.openclaw/workspace
cd ~/.openclaw/workspace
```

Create these core files:
- `SOUL.md` — who your agent is (name, personality, tone)
- `USER.md` — who you are (name, projects, preferences)
- `AGENTS.md` — operational rules (memory, heartbeat, group chat behavior)
- `MEMORY.md` — long-term memory (starts empty, grows over time)
- `TOOLS.md` — notes on your specific setup (credentials, device names, etc.)

---

## Step 6: Start OpenClaw

```bash
openclaw start
# or run as a background service:
openclaw gateway start
```

---

## Step 7: Connect External Services

### GitHub
1. Go to github.com/settings/tokens → Generate new token (classic)
2. Scope: `repo` only
3. Clone your repo:
```bash
git clone https://YOUR_PAT@github.com/yourorg/yourrepo
```

### Mailchimp
1. Mailchimp → Account → Extras → API Keys → Create key
2. Save in workspace: `echo '{"mailchimp_api_key": "YOUR_KEY"}' > ~/.openclaw/workspace/mailchimp-credentials.json`
3. Test: `curl --user "anystring:YOUR_KEY" https://usXX.api.mailchimp.com/3.0/`

### Google APIs (Ads, Calendar, Drive, Search Console)
This is the most complex part. See below.

---

## Google API Setup (Detailed)

### Step A: Create a Google Cloud Project
1. Go to https://console.cloud.google.com
2. Create new project (e.g. "cricket-tools")
3. Enable the APIs you need (search in API Library):
   - Google Ads API
   - Google Calendar API
   - Google Drive API
   - Google Search Console API

### Step B: OAuth Consent Screen
1. APIs & Services → OAuth consent screen
2. Type: External (or Internal if Google Workspace)
3. Fill in app name, contact email
4. Add test users (your email addresses)

### Step C: Create OAuth Credentials
1. APIs & Services → Credentials → Create Credentials → OAuth 2.0 Client ID
2. Type: **Web application**
3. Add redirect URI: `https://developers.google.com/oauthplayground`
4. Copy Client ID and Client Secret

### Step D: Get a Refresh Token
1. Go to https://developers.google.com/oauthplayground
2. Click ⚙️ gear → "Use your own OAuth credentials"
3. Enter your Client ID and Client Secret
4. Select the scopes you need (e.g. Google Ads API, Calendar, Drive)
5. Authorize APIs → sign in → Exchange authorization code for tokens
6. Copy the `refresh_token`

### Step E: Google Ads (additional steps)
1. Create a Manager (MCC) account at https://ads.google.com/home/tools/manager-accounts/
2. Link your Ad Grants account as a sub-account
3. Apply for Basic Access: Google Ads → ⚙️ → API Center → Apply for Basic Access
4. Wait for approval (1–3 business days)

### Store credentials
```bash
cat > ~/.openclaw/workspace/google-credentials.json << 'EOF'
{
  "client_id": "YOUR_CLIENT_ID",
  "client_secret": "YOUR_CLIENT_SECRET",
  "refresh_token": "YOUR_REFRESH_TOKEN",
  "developer_token": "YOUR_ADS_DEVELOPER_TOKEN"
}
EOF
chmod 600 ~/.openclaw/workspace/google-credentials.json
```

---

## Governance Files

Create these in your GitHub repo root so your agent's rules are documented and version-controlled:

- `README.md` — overview of the repo and agent
- `HARD_LIMITS.md` — explicit things the agent may never do
- `SECURITY_LOG.md` — log of security decisions and data handling
- `TODO.md` — shared task list

---

## Tips From Experience

- **Use one Google account for everything AI-related** (e.g. `webmaster@yourorg.org`) — mixing personal and work accounts causes OAuth headaches
- **Rotate your PATs and secrets periodically** — especially if shared anywhere even accidentally
- **The workspace folder is your agent's brain** — back it up, commit it to a private repo
- **HARD_LIMITS.md is important** — write down every "never do this" rule the moment you think of it
- **Start with read-only access** for new services, add write access intentionally

---

*Last updated: 2026-04-01 by Cricket*
