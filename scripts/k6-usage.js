import http from 'k6/http';
import { check } from 'k6';

const baseUrl = __ENV.BASE_URL || 'http://localhost:8000';
const apiKey = __ENV.API_KEY;
const customerId = Number(__ENV.CUSTOMER_ID || 1);
const duration = __ENV.DURATION || '60s';
const vus = Number(__ENV.VUS || 20);

if (!apiKey) {
  throw new Error('Set API_KEY to a valid merchant key.');
}

export const options = {
  scenarios: {
    usage_ingestion: {
      executor: 'constant-vus',
      vus,
      duration,
      gracefulStop: '5s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<500', 'p(99)<1000'],
  },
};

export default function () {
  const key = `${__VU}-${__ITER}-${Date.now()}`;
  const response = http.post(
    `${baseUrl}/api/v1/usage`,
    JSON.stringify({
      customer_id: customerId,
      units: 1,
      occurred_at: new Date().toISOString(),
    }),
    {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-API-Key': apiKey,
        'Idempotency-Key': key,
      },
      tags: { endpoint: 'usage' },
    },
  );

  check(response, {
    'usage accepted or rate limited': (result) => result.status === 201 || result.status === 429,
  });
}
