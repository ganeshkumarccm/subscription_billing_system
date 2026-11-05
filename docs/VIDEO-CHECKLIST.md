# 5-10 minute video checklist

Use the demo seed data and keep the browser DevTools open on **Network** when
showing requests and responses.

## 1. Register and log in

- Open `/register`.
- Register a new merchant and user.
- Show the successful response and redirect to `/dashboard`.
- Log out, open `/login`, and log in with the new credentials.
- Select the login request in Network and show:
  - `POST /api/v1/auth/login`
  - the `access_token` response
  - a subsequent authenticated request containing
    `Authorization: Bearer <token>`

## 2. Dashboard

- Open `/dashboard`.
- Show the loading state, summary cards, top customers, churn-risk card, and
  30-day usage chart.
- Select the dashboard request and show `GET /api/v1/dashboard`.
- Mention that the dashboard refreshes automatically every 60 seconds.

## 3. Expired-session redirect

- Open DevTools **Application/Storage > Local Storage**.
- Delete the `jwt` entry.
- Refresh `/dashboard` or navigate to another authenticated page.
- Show the redirect to `/login` (or `/login?expired=1` when an authenticated
  request receives a 401).

## 4. Tenant isolation

- Use a token for Merchant A.
- Request a customer ID that belongs to Merchant B:
  `GET /api/v1/customers/{merchant-b-customer-id}`.
- Show the `404` response and explain that cross-merchant customer details are
  not disclosed.

## 5. Idempotent usage

- Use a valid Merchant A API key and customer ID.
- Send the same request twice with the same `Idempotency-Key`:

```bash
curl -X POST http://localhost:8000/api/v1/usage ^
  -H "Accept: application/json" ^
  -H "Content-Type: application/json" ^
  -H "X-API-Key: <merchant-api-key>" ^
  -H "Idempotency-Key: demo-video-key-001" ^
  -d "{\"customer_id\":<customer-id>,\"units\":5}"
```

- Repeat the exact command without changing the idempotency key.
- Show that the first response is `201`, the replay is `200`, and the daily
  aggregate increases only once.
