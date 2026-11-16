/**
 * Phoenix Capital CRM — lightweight lead form submit helper for phoenixcapital site.
 *
 * Usage:
 *   <script src="https://YOUR-CRM/embeds/phoenixcapital-lead-form.js"
 *           data-endpoint="https://YOUR-CRM/api/leads/website"
 *           data-api-key="YOUR_WEBSITE_LEADS_API_KEY"></script>
 *   <form id="phoenix-lead-form">…fields first_name, last_name, phone, email, message, consent…</form>
 *
 * Include honeypot inputs named website_url and company_fax (hidden) for spam protection.
 */
(function () {
  var script = document.currentScript;
  var endpoint = (script && script.getAttribute('data-endpoint')) || '/api/leads/website';
  var apiKey = (script && script.getAttribute('data-api-key')) || '';
  var formSelector = (script && script.getAttribute('data-form')) || '#phoenix-lead-form';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    var form = document.querySelector(formSelector);
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var data = Object.fromEntries(new FormData(form).entries());
      data.consent = !!(form.querySelector('[name=consent]') && form.querySelector('[name=consent]').checked);
      data.page_url = window.location.href;
      data.idempotency_key = 'js-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);

      fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Phoenix-Api-Key': apiKey
        },
        body: JSON.stringify(data)
      }).then(function (res) {
        return res.json().then(function (json) {
          var ev = new CustomEvent('phoenix:lead-result', { detail: { ok: res.ok, status: res.status, body: json } });
          form.dispatchEvent(ev);
          if (res.ok) form.reset();
        });
      }).catch(function (err) {
        form.dispatchEvent(new CustomEvent('phoenix:lead-result', { detail: { ok: false, error: String(err) } }));
      });
    });
  });
})();
