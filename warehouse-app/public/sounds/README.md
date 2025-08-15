# Audio Files

This directory contains audio files for system alerts.

## Files
- `alert.mp3` - Alert sound for low inventory notifications
- `alert.ogg` - OGG format fallback for older browsers

## Notes
- If no audio files are present, the system will automatically generate a fallback beep sound using Web Audio API
- Audio files should be short (1-3 seconds) and small in size (< 2MB)
- Supported formats: MP3, OGG, WAV

## Adding Custom Sounds
1. Place your audio file in this directory
2. Name it `alert.mp3` for MP3 format or `alert.ogg` for OGG format
3. The system will automatically detect and use the file
4. Test the audio by triggering a low inventory alert in the dashboard
