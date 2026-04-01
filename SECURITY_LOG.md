# Security and Data Risk Log

This document logs every security-relevant decision, risk discussion, and protection measure established between Kate and Cricket.

---

## 2026-04-01 — Initial Setup Session

### Credentials Shared
- Google Ads developer token, OAuth client ID/secret, refresh token — stored in workspace/google-ads/credentials.json (chmod 600)
- Mailchimp API key — stored in workspace/mailchimp-credentials.json (chmod 600)
- GitHub Personal Access Token — stored in git remote URL config
- Google Calendar ICS URL (secret address) — stored in TOOLS.md

### Security Notes
- Several credentials were shared in Discord guild #general channel before full security posture was established
- Kate confirmed this is a private, invite-only server (Kate + Cricket only)
- Credentials are stored locally on the Cricket server with restricted file permissions
- GitHub PAT, Google OAuth client secret, and Mailchimp API key were shared in chat — recommend rotating these periodically

### Risks Identified
- **OAuth refresh token expiry:** Google refresh tokens for External apps expire after 7 days unless app is published. Monitor for auth failures.
- **Developer token (Google Ads):** Test Account only until Basic Access approved. Application submitted 2026-04-01.
- **Mailchimp send capability:** API technically can send email. Hard limit established and documented.

### Protections in Place
- All credential files are chmod 600 (owner read/write only)
- Hard limits documented in HARD_LIMITS.md
- No credentials stored in code or committed to git
- Separate "Cricket" Google Calendar to avoid personal calendar access

---

*Append new entries below as security discussions occur.*
