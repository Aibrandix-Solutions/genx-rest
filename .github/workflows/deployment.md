Is it uploading all files every time?
Effectively yes.
Your two SFTP steps point to:

local_path: "deploy_app/*" -> /public_html/laravel/
local_path: "deploy_public/*" -> /public_html/
Because GitHub runners are ephemeral and this action has no persisted local/remote diff cache in your workflow, each run performs a broad upload pass over all artifact files. That matches your ~30 min app upload.

Why it’s slow in current setup
SFTP is high-overhead for many small files (Laravel/Modules/resources).
You rebuild and republish full artifact trees every run.
No explicit changed-file gating before upload.
public/build can change frequently due to hashed assets, increasing churn.
Best improvements (high impact)
Switch from SFTP action to rsync over SSH (if host allows SSH commands; yours does, since appleboy/ssh-action works).
Use:
rsync -az --delete --checksum ... for app/public
uploads only changed files + deletes removed files cleanly.
If SFTP must remain, use an action/tool that supports true differential sync with state/manifest, not wildcard full tree push each run.
Separate deployment scopes:
only sync deploy_public/build when frontend changed
only sync app/Modules/resources when backend changed
Optional artifact tarball strategy:
upload one compressed tar, extract server-side (fewer SFTP operations).
If you want, I can directly refactor your workflow to rsync-based incremental deploy and keep your current composer/migrate steps unchanged.