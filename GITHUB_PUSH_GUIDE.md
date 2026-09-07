# Smart Slope: GitHub Publishing Guide

This guide explains how to publish the Smart Slope prototype to GitHub from the project folder.

Project folder:

```text
/Applications/XAMPP/xamppfiles/htdocs/smart_slope
```

The commands below use the standard `git` command-line tool. GitHub CLI (`gh`) is optional.

---

## 1. Prepare the project

Before publishing, confirm that:

- Apache and MySQL are not required for the Git upload itself.
- The project still works locally at `http://localhost/smart_slope/login.php`.
- The latest version of `TESTING.md` and `PROJECT_DOCUMENTATION.md` is present.
- No real passwords, API keys, private sensor credentials, or government-sensitive data are present.
- `register_admin.php` will be deleted or protected after the first local setup.

This project currently uses the local XAMPP database configuration in `config.php`:

```php
$username = 'root';
$password = '';
```

That is a local XAMPP prototype setting, not a production configuration. Before publishing a public repository, review `config.php` and remove any real credentials. The current blank local password is not a secret, but the application should still use a dedicated database user before deployment.

The repository also contains the demo default password in `register_admin.php` and documentation. Do not reuse that password anywhere outside the local prototype.

---

## 2. Install Git if needed

Check whether Git is installed:

```bash
git --version
```

If macOS asks to install Command Line Tools, accept the prompt and wait for installation to finish. Then close and reopen the terminal and run the command again.

You can also install Git with Homebrew if Homebrew is already installed:

```bash
brew install git
```

If `brew` is not available, use Apple's Command Line Tools prompt or install Git from the official Git website.

---

## 3. Configure Git identity

Run these commands once on the computer. Use the name and email associated with your GitHub account if possible.

```bash
git config --global user.name "Your Name"
git config --global user.email "your-email@example.com"
```

Check the configuration:

```bash
git config --global --list
```

Use the email shown on your GitHub account, or use GitHub's private `noreply` email if you do not want your personal email in commits.

---

## 4. Create an empty GitHub repository

1. Sign in at [https://github.com](https://github.com).
2. Click the **+** button in the upper-right corner.
3. Choose **New repository**.
4. Use a repository name such as:

```text
smart-slope
```

5. Add a short description, for example:

```text
Prototype landslide early-warning dashboard for three Baguio monitoring locations.
```

6. Choose **Private** for a school, government, or internal prototype unless the project has been approved for public release.
7. Do not add a README, `.gitignore`, or license on GitHub because this project will add its own files locally.
8. Click **Create repository**.
9. Keep the repository page open so you can copy its remote URL.

The remote URL will look like one of these:

```text
https://github.com/YOUR-USERNAME/smart-slope.git
```

or:

```text
git@github.com:YOUR-USERNAME/smart-slope.git
```

---

## 5. Open the project folder

In Terminal, run:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/smart_slope
```

Confirm you are in the correct folder:

```bash
pwd
ls
```

You should see files such as `index.php`, `db_schema.sql`, `TESTING.md`, and `PROJECT_DOCUMENTATION.md`.

---

## 6. Initialize the local Git repository

Run:

```bash
git init
```

The project is now a local Git repository.

Check what Git sees:

```bash
git status
```

The `.gitignore` included in this project excludes macOS metadata, temporary files, Python cache folders, virtual environments, and local environment overrides.

---

## 7. Review files before staging

This step is important. Do not skip it.

```bash
git status --short
git diff --stat
```

Review the files that will be committed:

```bash
git add -n .
```

The `-n` option is a dry run. It shows what would be staged without changing the index.

Check for possible credentials or private information:

```bash
grep -R -n -E "API_KEY|SECRET|TOKEN|PASSWORD|password|admin123" \
  --exclude-dir=.git \
  --exclude="*.pdf" \
  .
```

This project intentionally contains references to prototype credentials in documentation and `register_admin.php`. That is acceptable only for a private classroom/demo repository. For a public repository, remove the default password and replace setup instructions with environment-based credentials before staging.

---

## 8. Add and commit the project

Stage all approved files:

```bash
git add .
```

Review the staged file list:

```bash
git status --short
git diff --cached --stat
```

Create the first commit:

```bash
git commit -m "Initial Smart Slope prototype"
```

Check the commit:

```bash
git log --oneline -1
```

---

## 9. Connect the project to GitHub

Replace `YOUR-USERNAME` with your GitHub username:

```bash
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/smart-slope.git
git remote -v
```

Expected output includes the GitHub repository URL for `origin`.

If you accidentally added the wrong remote, correct it with:

```bash
git remote set-url origin https://github.com/YOUR-USERNAME/smart-slope.git
```

---

## 10. Push the project

Run:

```bash
git push -u origin main
```

GitHub may open a browser for authentication. If Terminal asks for a password, do not use your normal GitHub account password. Use a GitHub personal access token or authenticate through GitHub CLI/browser login.

After a successful push, refresh the GitHub repository page. The project files should appear there.

---

## 11. Authentication choices

### HTTPS

Use an HTTPS remote:

```bash
git remote set-url origin https://github.com/YOUR-USERNAME/smart-slope.git
```

GitHub no longer accepts normal account passwords for Git pushes. Use one of:

- Git Credential Manager.
- A GitHub personal access token.
- GitHub CLI authentication.

### GitHub CLI

If `gh` is installed:

```bash
gh auth login
gh repo create YOUR-USERNAME/smart-slope --private --source=. --remote=origin --push
```

If the repository already exists, use the normal `git remote add` and `git push` commands instead.

### SSH

If an SSH key is already configured:

```bash
git remote set-url origin git@github.com:YOUR-USERNAME/smart-slope.git
git push -u origin main
```

If SSH is not configured, HTTPS is usually simpler for the first push.

---

## 12. Verify the GitHub repository

After pushing, verify:

1. `index.php` is visible.
2. `PROJECT_DOCUMENTATION.md` is visible.
3. `TESTING.md` is visible.
4. `db_schema.sql` is visible.
5. `assests/css` and `assests/js` are visible.
6. The repository visibility is correct: private or public as intended.
7. No secrets or private data were accidentally committed.
8. The latest commit has the expected message.

To verify the remote copy from Terminal:

```bash
git status
git remote -v
git log --oneline --decorate -3
```

A clean working tree should report:

```text
nothing to commit, working tree clean
```

---

## 13. How to publish future changes

After editing the project:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/smart_slope
git status
git diff
git add .
git commit -m "Describe the change"
git push
```

Use descriptive commit messages, for example:

```text
Add selected-location report export
Improve automatic risk alerts
Fix weather API validation
Update presentation documentation
```

Before each push, run the project checks listed in `TESTING.md`.

---

## 14. Recommended repository files

The GitHub repository should include:

- `PROJECT_DOCUMENTATION.md`
- `TESTING.md`
- `db_schema.sql`
- PHP application files
- Python model scripts
- `landslide_model.pkl`
- `data/historical_weather.csv`
- `sample_telemetry.csv`
- Local Bootstrap, jQuery, and Chart.js assets
- `.gitignore`

The model file, PDF, and CSV files in this prototype are small enough for normal GitHub storage. If future model files become larger than GitHub's file limit, use Git LFS instead of committing them directly.

---

## 15. Important GitHub safety notes

- Git history keeps old commits. Removing a password from the latest file does not remove it from previous commits.
- If a real password or token is committed, rotate it immediately and remove it from Git history with an approved history-rewrite procedure.
- Do not commit production database dumps containing personal information.
- Do not commit real sensor locations or resident information unless approved.
- Keep this prototype repository private unless public release has been approved.
- GitHub hosts the source code; it does not run the PHP/MySQL application automatically.
- A person cloning the repository still needs XAMPP, MariaDB, Python dependencies, database setup, and the steps in `PROJECT_DOCUMENTATION.md`.

---

## 16. Clone and run on another computer

After the repository is published, another developer can clone it with:

```bash
git clone https://github.com/YOUR-USERNAME/smart-slope.git
cd smart-slope
```

Then follow the local setup instructions in `PROJECT_DOCUMENTATION.md`:

1. Copy the folder into the XAMPP `htdocs` directory.
2. Start Apache and MySQL.
3. Import `db_schema.sql`.
4. Install Python packages.
5. Run the one-time admin setup.
6. Open `http://localhost/smart_slope/login.php`.
7. Run the checklist in `TESTING.md`.

---

## 17. Short command summary

Once Git is installed and the GitHub repository is created:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/smart_slope
git init
git add .
git commit -m "Initial Smart Slope prototype"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/smart-slope.git
git push -u origin main
```

Replace `YOUR-USERNAME` before running the remote commands.
