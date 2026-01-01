# Implementation Plan - Multi-Image Upload Feature

# Goal Description
Enable the upload and processing of multiple Pokemon card images in a single session. The system will process each image through the existing workflow (Crop -> OCR -> AI -> Save) but will streamline the interaction by creating a processing queue and a results list, allowing the user to crop images in sequence while previous ones are processed in the background.

## User Review Required
> [!IMPORTANT]
> **UX Change**: The "Single Result View" will be replaced by a "Results List". The cropping interface will now act as a "Feeder" that cycles through the uploaded images.

## Proposed Changes

### Frontend Components

#### [MODIFY] [upload.blade.php](file:///c:/Progetti/php/lib-pokemon/resources/views/ocr/upload.blade.php)
- **HTML Structure**:
    - Update prompt logic logic to support multiple files.
    - Create a new `#processingQueueSection` to hold the Cropper and "Next" controls.
    - Create a new `#resultsListSection` to append processed cards dynamically.
    - Convert existing `#resultCard` and `#cardDetailsSection` into a reusable **JavaScript Template** (or hidden DOM element to clone) so we can instantiate one for each processed image.
- **JavaScript Logic**:
    - **State Management**: Introduce `fileQueue` (Array) and `processedItems` (Map/Array).
    - **Queue Loop**: Logic to load `fileQueue[0]` into Cropper, slice it on "Next", and loop.
    - **Dynamic Rendering**: Function `createResultCard(data)` that clones the template, assigns unique IDs (e.g., `card-123`), and binds specific event listeners for that card (Enhance, Save, Discard).
    - **Parallelism**: Ensure `fetch` calls for OCR and AI are independent per card instance.

### Backend Components
*No structural changes required in Controllers.* existing endpoints `ocr.process`, `ocr.enhance`, `ocr.confirm` support the isolated flow per card.

## Verification Plan

### Automated Tests
- None planned for UI behavior (Jest/Cypress not configured).

### Manual Verification
1.  **Batch Upload**: Select 3 different images.
2.  **Queue Flow**:
    - Verify Image 1 loads in Cropper.
    - Click "Analyze & Next".
    - Verify Image 2 loads immediately.
    - Verify Image 1 appears in "Results List" with loading state -> success state.
3.  **Independence**:
    - While cropping Image 3, click "AI Enhance" on Result 1. Verify it works without freezing the cropper.
4.  **Completion**:
    - Save all 3 cards.
    - Verify they appear in "Recent Scans" (or redirect to collection).
