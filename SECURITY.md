# Security Policy

## Supported release

Only the latest deployed release is supported with security fixes.

## Reporting

Report suspected vulnerabilities privately to the system owner. Do not place
credentials, student data, payment evidence, or exploit details in public issue
trackers. The production operator must replace this paragraph with the client's
security contact before launch.

## Required controls

- Production must use HTTPS, `APP_DEBUG=false`, a unique `APP_KEY`, patched
  dependencies, least-privilege database/S3 credentials, and a real SMTP provider.
- Paid content and payment evidence must remain on the configured private disk.
- Administrator accounts must use unique passwords and the smallest required role.
- Database restores are disabled unless an approved maintenance window explicitly
  sets `ALLOW_DATABASE_RESTORE=true`.
- Logs and backups must be encrypted, access-controlled, retained according to the
  client's policy, and tested through scheduled restore drills.
- Compromised credentials must be rotated immediately, including any values that
  appeared in Git history.

## Credential rotation runbook (recorded 2026-09-27)

The following values were committed to Git history in earlier commits
(`.env.cpanel` and `README.md`) and MUST be treated as compromised. They have
been removed from the working tree and replaced with placeholders, but Git
history still contains them until it is purged.

| Value | Where it leaked | Action required |
|-------|-----------------|-----------------|
| Production `APP_KEY` | `.env.cpanel`, `README.md` | Rotate in cPanel `.env` (`php artisan key:generate`); re-encrypt any encrypted columns. |
| cPanel MySQL password | `.env.cpanel`, `README.md` | Change the MySQL user password in cPanel → MySQL Databases; update server `.env`. |
| Initial admin password | `.env.cpanel` | Sign in and change it; remove `INITIAL_ADMIN_*` from server `.env` afterwards. |

Purge history on the deploy clone (after rotating every value above):

```bash
git filter-repo --path .env.cpanel --path .env.production --invert-paths
git push --force-with-lease
```

Never commit real values again — `.env*` is gitignored; use
`.env.cpanel.template` / `.env.production.template` as the committed reference.

## Release checks

Run `php artisan test`, `composer audit --locked`, `npm audit --omit=dev`, and
`npm run build`. Review authorization, uploads, queues, backups, and audit logs
in staging before each release.
