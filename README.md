# am2tec/support-sdk

SDK Laravel para o `support_api` (server-side: usa `X-App-Key` / `X-App-Secret`).

```env
SUPPORT_API_URL=https://support.am2tec.com
SUPPORT_API_APP_KEY=...
SUPPORT_API_APP_SECRET=...
```

```php
$token = app(\Am2tec\SupportSdk\SupportSdk::class)->mintWidgetToken(
    ['external_user_id' => $user->id, 'name' => $user->name, 'email' => $user->email],
    ['external_id' => $tenant->id, 'name' => $tenant->name, 'slug' => $tenant->slug],
);
```

`SupportSdk::client()` devolve o `SupportClient` (`post`/`get` em `/api/v1/*`).
Nunca exponha app key/secret ao browser — só o token resultante.
