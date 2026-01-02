# Set Organization and Card Index Redesign

**Date**: 2026-01-02  
**Feature**: Card index page redesign with set-based organization

## Overview

The card collection index has been redesigned to display smaller cards organized by Pokemon card sets, with new capabilities for viewing, editing, and managing set assignments directly from the collection view.

## Key Changes

### Visual Updates
- **Smaller Card Display**: Cards now appear in a more compact 6-per-row grid (on desktop) instead of the previous 3-per-row layout
- **Set Organization**: Cards are grouped into collapsible sections by their assigned set
- **"Senza Set" Section**: Cards without an assigned set appear in a dedicated section at the bottom

### New Capabilities

#### Set Assignment
- Users can assign a Card Set during the upload process via a new dropdown in the manual entry modal
- The `card_set_id` field is now part of the card creation/update workflow

#### Collection Organization  
- Sets are displayed as collapsible accordion sections with:
  - Set name header
  - Card count badge
  - Expand/collapse functionality for better organization

### Technical Implementation

#### Backend Changes

**Database Relationships**:
- Leverages existing `CardSet` model and `card_set_id` foreign key
- `PokemonCard` has `belongsTo` relationship with `CardSet`

**Controller Updates** (`CardUploadController`):
```php
// index() - Groups cards by set
$cards = PokemonCard::with('cardSet')
    ->where('status', PokemonCard::STATUS_COMPLETED)
    ->orderBy('card_set_id')
    ->orderBy('card_name')
    ->get();

$cardsBySet = $cards->groupBy(function($card) {
    return $card->cardSet ? $card->cardSet->name : null;
});
```

**New API Endpoints**:
- `PUT /cards/{card}/update` - Update card details from index
- `POST /cards/assign-set` - Bulk assign sets to multiple cards  
- `GET /cards/api/card-sets` - Retrieve all card sets for dropdown population

#### Frontend Changes

**index.blade.php**:
- Redesigned with accordion-style set sections
- Smaller card thumbnails with essential info only
- View/Edit/Delete action buttons per card
- Fullscreen image zoom capability
- JavaScript functions for set collapse/expand

**upload.blade.php**:
- Added "Card Set" dropdown field in manual entry modal
- Dropdown populated from `/cards/api/card-sets` endpoint
- `card_set_id` included in save data

### User Workflow

1. **Upload**: User uploads cards and can optionally assign a set during manual entry
2. **Index View**: Navigate to collection to see cards organized by set
3. **Set Management**: 
   - Cards without sets appear in "Senza Set" section
   - Users can edit cards to assign/change sets
4. **Organization**: Click set headers to expand/collapse sections for easier browsing

## Benefits

- **Better Organization**: Collectors can easily view their collection grouped by set
- **Progress Tracking**: See at a glance how many cards from each set you own
- **Compact Display**: More cards visible on screen at once
- **Flexible Assignment**: Assign sets during upload or later from the collection view

## Future Enhancements

Potential features for future iterations:
- Bulk set assignment from index page (select multiple cards)
- Set completion percentage indicators
- Filter/search by set
- Sort options within each set section
