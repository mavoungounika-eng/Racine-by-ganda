# GitHub Release Instructions - v1.0.0-pos-audit-ready

## 📋 Step-by-Step Instructions

### Step 1: Navigate to Releases
1. Go to GitHub repository
2. Click on **"Releases"** (right sidebar)
3. Click **"Create a new release"**

### Step 2: Configure Release

**Choose a tag:**
```
v1.0.0-pos-audit-ready
```

**Release title:**
```
v1.0.0 — POS Audit-Ready Architecture
```

**Description:** (Copy-paste exactly)
```
This release certifies the POS architecture as audit-ready.

Key guarantees:
- Separation of field facts and accounting truth
- Mandatory cash drawer sessions
- Cash pending until session close
- Intent-Based Finance integration
- Idempotence at DB, Redis and application levels
- Incident runbook with human authority controls
- Full audit trail and temporal integrity checks

This version is production-hardened and suitable for:
- Pilot deployment
- Financial audit
- Governance review

Breaking changes:
- POS orders no longer trigger PaymentRecorded events
- Cash accounting is deferred until session closure

Status: GOVERNANCE APPROVED
```

### Step 3: Attach Documentation
- [ ] Attach `RELEASE_NOTES_POS.md`
- [ ] Attach `docs/INCIDENT_POS.md`

### Step 4: Publish
- [ ] Check "Set as the latest release"
- [ ] Click **"Publish release"**

---

## ✅ Verification

After publishing, verify:
- [ ] Release is visible on GitHub
- [ ] Tag `v1.0.0-pos-audit-ready` exists
- [ ] Documentation is attached
- [ ] Status shows "Latest"

---

**IMPORTANT:** No modifications allowed after publication without new release.
