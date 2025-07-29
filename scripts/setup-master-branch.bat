@echo off
REM Script to set up master as the default and only branch
REM This script helps clean up the repository to use only the master branch

echo ========================================
echo Setting up Master as Default Branch
echo ========================================
echo.

echo Current branch status:
git branch -a
echo.

echo Current remote branches:
git ls-remote --heads origin
echo.

echo ========================================
echo Manual Steps Required:
echo ========================================
echo.
echo 1. Go to GitHub repository settings:
echo    https://github.com/kepilot/police_tests/settings/branches
echo.
echo 2. Change default branch from 'main' to 'master'
echo.
echo 3. After changing the default branch, run:
echo    git push origin --delete main
echo.
echo 4. Verify only master branch exists:
echo    git ls-remote --heads origin
echo.

echo ========================================
echo Current Local Branch: %git_branch%
echo ========================================
git branch --show-current
echo.

echo ========================================
echo Next Steps:
echo ========================================
echo 1. Complete the manual steps above
echo 2. Run: git push origin --delete main
echo 3. Run: git ls-remote --heads origin
echo 4. Verify only 'master' branch exists
echo.

pause 