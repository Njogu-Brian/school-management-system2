#!/bin/bash
# Deployment script to sync JavaScript files from codebase to public web folder
# Safe script that only copies files and doesn't delete anything

set -e  # Exit on error

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Paths
CODEBASE_PATH="$HOME/laravel-app/school-management-system"
PUBLIC_JS_PATH="$HOME/erp.royalkingsschools.sc.ke/js"
SOURCE_JS_PATH="$CODEBASE_PATH/public/js"

echo -e "${GREEN}Starting JavaScript assets deployment...${NC}"

# Check if codebase path exists
if [ ! -d "$CODEBASE_PATH" ]; then
    echo -e "${RED}Error: Codebase path not found: $CODEBASE_PATH${NC}"
    exit 1
fi

# Check if source js directory exists
if [ ! -d "$SOURCE_JS_PATH" ]; then
    echo -e "${YELLOW}Warning: Source JS directory not found: $SOURCE_JS_PATH${NC}"
    echo "No JavaScript files to sync."
    exit 0
fi

# Create destination directory if it doesn't exist
if [ ! -d "$PUBLIC_JS_PATH" ]; then
    echo -e "${YELLOW}Creating directory: $PUBLIC_JS_PATH${NC}"
    mkdir -p "$PUBLIC_JS_PATH"
fi

# Count files to copy
JS_FILES=$(find "$SOURCE_JS_PATH" -maxdepth 1 -name "*.js" -type f | wc -l)

if [ "$JS_FILES" -eq 0 ]; then
    echo -e "${YELLOW}No JavaScript files found in source directory.${NC}"
    exit 0
fi

echo -e "${GREEN}Found $JS_FILES JavaScript file(s) to sync${NC}"

# Copy each JavaScript file
COPIED=0
SKIPPED=0

for file in "$SOURCE_JS_PATH"/*.js; do
    if [ -f "$file" ]; then
        filename=$(basename "$file")
        destination="$PUBLIC_JS_PATH/$filename"
        
        # Copy file
        if cp "$file" "$destination"; then
            # Set correct permissions (644 = readable by web server)
            chmod 644 "$destination"
            echo -e "${GREEN}✓ Copied: $filename${NC}"
            ((COPIED++))
        else
            echo -e "${RED}✗ Failed to copy: $filename${NC}"
            ((SKIPPED++))
        fi
    fi
done

echo ""
echo -e "${GREEN}Deployment complete!${NC}"
echo -e "Files copied: $COPIED"
if [ "$SKIPPED" -gt 0 ]; then
    echo -e "${YELLOW}Files skipped: $SKIPPED${NC}"
fi
echo ""

