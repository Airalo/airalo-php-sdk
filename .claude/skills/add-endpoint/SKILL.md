---
name: add-endpoint
description: Add a new Partner API endpoint or public SDK method to the Airalo PHP SDK end to end, covering the constant, service, both facades, mock, README and tests. Use when asked to add, wrap or expose a new endpoint or API method, or to extend an existing one with new parameters.
---

# Add an endpoint to the SDK

This workflow follows the "Adding or changing an endpoint" checklist in `AGENTS.md`. Work through the steps in order, and don't skip any: missing one facade or the README entry is the most common review failure.

## 1. Gather the spec

Confirm these with the developer before writing code, and ask for anything you can't infer:

- Endpoint path and HTTP method (`GET` / `POST` / `DELETE`).
- Request parameters: required vs optional, types, validation rules.
- Whether it needs request signing. Order, top-up, voucher and future-order calls are signed (`Signature`); read-only calls are not.
- Whether responses should be cached, and for how long.
- The public method name and signature. Match the naming of neighbouring methods (`getX`, `xBulk`, …).
- Which ticket it belongs to (needed for the commit title).

## 2. Pick a template

Find the closest existing method and copy its shape instead of inventing a new one:

| Kind of call | Template |
|---|---|
| Simple GET, no params | `CompatibilityDevicesService::getCompatibleDevices()` |
| GET with params + caching | `SimService::simUsage()` (uses `CacheTrait::cacheRemember()`) |
| Signed POST | `TopupService`, `OrderService` |
| Bulk / parallel requests | `SimService::simUsageBulk()` (uses `MultiCurlResource`) |

Read the template and its facade methods before continuing.

## 3. Implement

1. **Constant:** add the path slug to `src/Constants/ApiConstants.php` next to related slugs.
2. **Service:** add the method to the relevant `src/Services/*Service.php`, or create a new service that follows the template's constructor. Validate every input first and throw `AiraloException` with a clear message **before** any HTTP call. Return `?EasyAccess`.
3. **Wiring (new service only):** add a property and construct it in **both** `Airalo::initServices()` and `AiraloStatic::initServices()`, respecting the `$pool` pattern.
4. **Facades:** add the public method to **both** `src/Airalo.php` and `src/AiraloStatic.php`, with identical parameters, defaults, return type and PHPDoc. The static version delegates through `self::$service`.
5. **Mock:** if `tests/Mock/AiraloMock.php` mirrors neighbouring methods, add the new one with a matching `setX()` if needed.
6. **README:** add an entry under "Methods Interface" in the same format as its neighbours: signature, a description, a link to the relevant Partner API docs page, and object plus static usage examples. Use placeholder credentials (`<YOUR_API_CLIENT_ID>`) only.
7. **Tests:** add or extend a test under `tests/Services/`. Mock `Config`, `CurlResource`, `MultiCurlResource` and `CacheInterface` as `tests/Services/SimServiceTest.php` does. Cover the happy path and each validation failure (use a `@dataProvider`). Never call the real API.

Constraints: stay **PHP 8.0 compatible** (no enums, `readonly`, `never`, `new` in initializers or first-class callables), add no new Composer dependencies, and don't break existing signatures.

## 4. Verify

1. `composer phpstan`, then fix and rerun until it's clean. Don't touch the baseline.
2. `composer phpunit`, then fix and rerun until it's green.
3. Run the `sdk-reviewer` agent on the diff and address any blockers.

## 5. Hand off

Summarise the files you changed and propose a commit title such as `feat: <what the method does> (<TICKET>)`. Don't commit or push unless the developer asks.
