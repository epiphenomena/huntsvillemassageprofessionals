REMOTE = "timlawles@dream:hmp.timlawles.com"

task :default => :sync

# Deploy to the test server. Never touches the server database or config:
#  - storage db (*.sqlite*) is excluded so server data is never overwritten
#  - config.php is excluded so server secrets (CRM password, SMTP_PASS) are kept
#  - tools/, the source scans (*.jpeg) and the python venv are dev-only
task :sync do
    sh "rsync -avzzh --progress --delete --exclude .git --exclude Rakefile " \
       "--exclude tools --exclude .venv --exclude '*.jpeg' --exclude '*.sqlite*' " \
       "--exclude config.php ./ #{REMOTE}/"
end

# Same as sync but a dry run — preview what would change without touching the server.
task :dryrun do
    sh "rsync -avzzh --dry-run --delete --exclude .git --exclude Rakefile " \
       "--exclude tools --exclude .venv --exclude '*.jpeg' --exclude '*.sqlite*' " \
       "--exclude config.php ./ #{REMOTE}/"
end

# Push config.php explicitly (overwrites server config — secrets included).
# Use on first deploy, or when you intend to update the server configuration.
task :pushconfig do
    sh "rsync -avzh config.php #{REMOTE}/config.php"
end

# Pull a copy of the server database for local development/testing.
# Backs up any existing local copy first; sync never pushes it back.
task :pulldb do
    db = "storage/hmp.sqlite"
    if File.exist?(db)
        backup = "#{db}.backup-#{Time.now.strftime('%Y%m%d-%H%M%S')}"
        cp db, backup
        puts "Backed up local db to #{backup}"
    end
    sh "rsync -avzh #{REMOTE}/storage/hmp.sqlite #{db}"
    puts "Server database copied to #{db} (local only - sync never pushes it back)"
end

# Run the dev server locally (auto-creates storage/hmp.sqlite with demo data).
task :dev do
    sh "php -S 127.0.0.1:8000"
end

# Regenerate the branded .docx intake/consent forms (requires python-docx).
task :forms do
    sh "python3 tools/generate_forms.py"
end
