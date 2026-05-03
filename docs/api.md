# API Reference

Support Hub exposes a REST API that allows any external system (ERP, CRM, e-commerce platform, etc.) to create and manage support tickets programmatically.

## Authentication

All API endpoints require an `Authorization` header with a Bearer token corresponding to a client's API key.

```
Authorization: Bearer {api_key}
Content-Type: application/json
```

The API key is generated when you create a client in Support Hub under **Clients > Add Client**. Each client (company) has its own unique key.

---

## Endpoints

### `GET /api/ping`

Validates the API key and returns basic client information. Use this to verify connectivity from your system's settings page.

**Request:**
```http
GET /api/ping
Authorization: Bearer {api_key}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Connection OK",
  "client": "Acme Corp",
  "identifier": "acme"
}
```

---

### `POST /api/tickets`

Creates a new support ticket in Support Hub. The ticket is placed in the **To Do** column and auto-assigned if a team member with matching module expertise is found.

If Gemini AI is configured, classification runs asynchronously after the response is sent — it does not block your webhook.

**Request body:**

| Field | Type | Required | Description |
|---|---|---|---|
| `reporter_name` | string | Yes | Full name of the person reporting the issue |
| `subject` | string | Yes | Short title of the issue (max 255 chars) |
| `description` | string | Yes | Full description of the issue |
| `ticket_type` | string | Yes | `bug` · `configuration` · `question` · `feature_request` |
| `module` | string | Yes | Module key (must match a key from `config/support.php`) |
| `reporter_email` | string | No | Email of the reporter |
| `external_ticket_id` | integer | No | ID of the ticket in your system (for cross-reference) |
| `sub_module` | string | No | Sub-module or feature area |
| `priority` | string | No | `low` · `medium` · `high` · `critical` (default: `medium`) |
| `steps_to_reproduce` | string | No | Step-by-step instructions to reproduce the issue |
| `expected_behavior` | string | No | What the user expected to happen |
| `browser_url` | string | No | URL the user was on when the issue occurred |
| `attachments` | array | No | Array of file attachments (see below) |

**Attachment object:**

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | Yes | Original filename (e.g. `screenshot.png`) |
| `type` | string | Yes | `image` · `video` · `document` |
| `base64` | string | No* | Base64-encoded file content |
| `url` | string | No* | Public URL to the file (if not sending base64) |

\* Either `base64` or `url` must be provided.

**Example request:**
```json
{
  "reporter_name": "Jane Smith",
  "reporter_email": "jane@acme.com",
  "external_ticket_id": 1042,
  "ticket_type": "bug",
  "module": "pos",
  "sub_module": "cash-register",
  "priority": "high",
  "subject": "Cash register does not close at end of day",
  "description": "When clicking 'Close Register', the system shows a spinner and never completes the process.",
  "steps_to_reproduce": "1. Open register\n2. Process any sale\n3. Click Close Register",
  "expected_behavior": "Register should close and print the Z report",
  "browser_url": "https://erp.acme.com/pos/register/close"
}
```

**Response `201`:**
```json
{
  "success": true,
  "ticket_number": "T-0042",
  "hub_ticket_id": 42
}
```

**Response `422` — Validation error:**
```json
{
  "success": false,
  "error": "Validation failed.",
  "errors": {
    "subject": ["The subject field is required."]
  }
}
```

---

### `POST /api/tickets/{hub_ticket_id}/status`

Updates the board column of a ticket. Optionally triggers a callback to your system when the ticket is resolved.

**URL parameter:** `hub_ticket_id` — the `hub_ticket_id` returned when the ticket was created.

**Request body:**

| Field | Type | Required | Description |
|---|---|---|---|
| `board_column` | string | Yes | Target column (see valid values below) |
| `notes` | string | No | Notes about the status change |
| `assigned_to` | integer | No | Team member ID to assign/reassign |
| `resolution_message` | string | No | Resolution summary (used when `board_column` is `done`) |

**Valid `board_column` values:**

| Value | Label |
|---|---|
| `to_do` | To Do |
| `in_progress` | In Progress |
| `blocked` | Blocked |
| `code_review` | Code Review |
| `qa_testing` | QA Testing |
| `ready_for_release` | Ready for Release |
| `done` | Done |

**Example request:**
```json
{
  "board_column": "done",
  "resolution_message": "Fixed in version 2.4.1 — cash register close now correctly finalizes the session.",
  "notes": "Root cause: session lock was not released on timeout"
}
```

**Response `200`:**
```json
{
  "success": true,
  "hub_ticket_id": 42,
  "old_column": "qa_testing",
  "new_column": "done"
}
```

---

### `POST /api/tickets/{hub_ticket_id}/client-response`

Sends a reply from the client (end user in your system) to the Support Hub. The message appears in the ticket timeline visible to the dev team.

**Request body:**

| Field | Type | Required | Description |
|---|---|---|---|
| `message` | string | Yes* | Reply text from the client |
| `responded_by` | string | No | Name to display as the author (default: `"Cliente"`) |
| `attachments` | array | No | Same attachment format as `POST /api/tickets` |

\* Required if no attachments are provided.

**Example request:**
```json
{
  "message": "Still happening after the update. I attached a new screenshot.",
  "responded_by": "Jane Smith",
  "attachments": [
    {
      "name": "error-after-update.png",
      "type": "image",
      "base64": "iVBORw0KGgoAAAANS..."
    }
  ]
}
```

**Response `200`:**
```json
{
  "success": true,
  "comment_id": 17
}
```

---

### `POST /api/tickets/{hub_ticket_id}/delete`

Soft-deletes a ticket. Use this when a ticket is cancelled or removed in your system and should no longer be visible in the hub.

**Response `200`:**
```json
{
  "success": true
}
```

---

## Callback (Hub → Your system)

When the Support Hub team replies to a ticket, it can optionally send an HTTP callback to your system so your users receive the response without leaving your platform.

The callback URL is configured per-client under **Clients > Edit**.

**Support Hub sends a `POST` to your callback URL with:**
```json
{
  "event": "team_reply",
  "hub_ticket_id": 42,
  "external_ticket_id": 1042,
  "ticket_number": "T-0042",
  "message": "We've identified the issue. A fix will be deployed tonight.",
  "replied_by": "Dev Team",
  "replied_at": "2025-10-14T20:30:00Z"
}
```

Your system should respond with HTTP `200` to acknowledge receipt.

---

## Error responses

All endpoints return a consistent error format:

| HTTP Status | Meaning |
|---|---|
| `401` | Missing or invalid API key |
| `404` | Ticket not found or does not belong to this client |
| `422` | Validation error — check the `errors` field |
| `500` | Internal server error |

```json
{
  "success": false,
  "error": "Error description"
}
```

---

## Integration checklist

When integrating Support Hub with your system:

- [ ] Create a client in Support Hub and copy the API key
- [ ] Configure your system with the Hub URL and API key
- [ ] Add a "Report issue" button in your system that calls `POST /api/tickets`
- [ ] Store the returned `hub_ticket_id` in your system for future status updates
- [ ] (Optional) Implement the callback endpoint to receive team replies
- [ ] Test the connection with `GET /api/ping`
- [ ] Configure modules in `config/support.php` to match your system's module names
