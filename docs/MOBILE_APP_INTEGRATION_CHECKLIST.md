# Mobile App Integration Checklist

## ✅ Completed Features

### Notification Preferences
- [x] Backend endpoints implemented
- [x] Mobile UI connected to API
- [x] GET `/notifications/preferences?format=flat`
- [x] PATCH `/notifications/preferences`
- [x] Testing complete
- [x] Production ready

## 🔧 Ready for Integration (Backend Complete)

All backend endpoints below are **fully implemented and tested**. Mobile integration can proceed immediately.

### 1. Security Settings

**Status:** Backend ✅ | Mobile Integration ⏳

**Endpoints Available:**
```
GET    /api/profile/security-settings
PATCH  /api/profile/security-settings
```

**Mobile Files to Update:**
- `lib/screens/profile/security_screen.dart`
- `lib/services/api_service.dart`

**API Methods to Add:**
```dart
Future<Map<String, dynamic>> getSecuritySettings() async {
  final response = await _dio.get('/profile/security-settings');
  return response.data['data'];
}

Future<void> updateSecuritySettings(Map<String, dynamic> settings) async {
  await _dio.patch('/profile/security-settings', data: settings);
}
```

**Integration Steps:**
1. Add API methods to `api_service.dart`
2. Load settings in `initState()` of `security_screen.dart`
3. Replace "coming soon" logic with real API calls
4. Test saving settings

**Expected Request/Response:**
```json
// GET Response
{
  "data": {
    "two_factor_enabled": false,
    "session_timeout_enabled": true,
    "session_timeout_minutes": 30,
    "biometric_enabled": false
  }
}

// PATCH Request
{
  "two_factor_enabled": false,
  "session_timeout_enabled": true,
  "session_timeout_minutes": 30
}
```

---

### 2. Profile Avatar Upload

**Status:** Backend ✅ | Mobile Integration ⏳

**Endpoints Available:**
```
POST   /api/profile/avatar
DELETE /api/profile/avatar
```

**Dependencies Needed:**
```yaml
# pubspec.yaml
dependencies:
  image_picker: ^1.0.4
```

**Mobile Files to Update:**
- `lib/screens/profile/edit_profile_screen.dart`
- `lib/services/api_service.dart`

**API Methods to Add:**
```dart
import 'dart:io';
import 'package:dio/dio.dart';

Future<String> uploadAvatar(File imageFile) async {
  final formData = FormData.fromMap({
    'avatar': await MultipartFile.fromFile(
      imageFile.path,
      filename: 'avatar.jpg',
    ),
  });

  final response = await _dio.post('/profile/avatar', data: formData);
  return response.data['avatar_url'];
}

Future<void> deleteAvatar() async {
  await _dio.delete('/profile/avatar');
}
```

**Integration Steps:**
1. Add `image_picker` dependency to `pubspec.yaml`
2. Run `flutter pub get`
3. Add API methods to `api_service.dart`
4. Implement image picker in `edit_profile_screen.dart` (line 156)
5. Add avatar display in profile UI
6. Test upload from camera and gallery

**Expected Response:**
```json
{
  "message": "Avatar uploaded successfully",
  "avatar_url": "https://chenesa-shy-grass-3201.fly.dev/storage/avatars/xyz.jpg",
  "updated_at": "2025-01-08T10:30:00.000000Z"
}
```

**Image Validation:**
- Formats: JPEG, PNG, JPG, GIF
- Max size: 2MB
- Auto-compression recommended in mobile app

---

### 3. Logout All Devices

**Status:** Backend ✅ | Mobile Integration ⏳

**Endpoint Available:**
```
POST /api/profile/logout-all
```

**Mobile Files to Update:**
- `lib/screens/profile/security_screen.dart`
- `lib/services/api_service.dart`

**API Method to Add:**
```dart
Future<Map<String, dynamic>> logoutAllDevices(String password) async {
  final response = await _dio.post(
    '/profile/logout-all',
    data: {'password': password},
  );
  return response.data;
}
```

**Integration Steps:**
1. Add API method to `api_service.dart`
2. Create password confirmation dialog
3. Call API on confirmation
4. Show success message with device count
5. Handle password errors

**Expected Request/Response:**
```json
// Request
{
  "password": "user_password"
}

// Success Response
{
  "message": "Successfully logged out from all other devices",
  "devices_logged_out": 3
}

// Error Response (422)
{
  "message": "Incorrect password",
  "errors": {
    "password": ["The password is incorrect"]
  }
}
```

**UI Flow:**
1. User taps "Logout All Devices"
2. Show AlertDialog requesting password
3. On confirm, call API
4. Show success/error message
5. Current session remains active

---

## Implementation Priority

### Phase 1: Security Settings (30 min)
**Easiest integration** - Similar pattern to notification preferences

1. Add API methods to `api_service.dart`
2. Load settings on screen init
3. Save on toggle changes
4. Test

### Phase 2: Logout All Devices (30 min)
**Simple dialog flow**

1. Add API method
2. Create password dialog
3. Wire up button
4. Test with multiple sessions

### Phase 3: Avatar Upload (60 min)
**Requires image picker setup**

1. Add image_picker dependency
2. Add API methods
3. Implement image selection (camera/gallery)
4. Add upload progress indicator
5. Update profile display
6. Test on iOS and Android

---

## Testing Checklist

### Security Settings
- [ ] Settings load from API on screen open
- [ ] Toggles reflect current settings
- [ ] Changes save to backend
- [ ] Success message appears
- [ ] Settings persist after app restart
- [ ] Error handling works

### Avatar Upload
- [ ] Camera capture works (both platforms)
- [ ] Gallery picker works (both platforms)
- [ ] Image uploads successfully
- [ ] Avatar displays in profile
- [ ] Delete avatar works
- [ ] Large images are handled (compression)
- [ ] Network errors are handled
- [ ] Upload progress shows

### Logout All Devices
- [ ] Password dialog appears
- [ ] Correct password logs out other devices
- [ ] Incorrect password shows error
- [ ] Success message shows device count
- [ ] Current session stays active
- [ ] Error handling works

---

## API Base URL

All endpoints use the base URL:
```
https://chenesa-shy-grass-3201.fly.dev/api
```

**Authentication Required:**
All endpoints require Bearer token:
```
Authorization: Bearer {user_token}
```

---

## Error Handling Pattern

All endpoints follow the same error response format:

```dart
try {
  await apiService.someMethod();
  // Success
} on DioException catch (e) {
  if (e.response?.statusCode == 422) {
    // Validation errors
    final errors = e.response?.data['errors'];
    // Show field-specific errors
  } else if (e.response?.statusCode == 401) {
    // Unauthorized - redirect to login
  } else {
    // Generic error
    final message = e.response?.data['message'] ?? 'An error occurred';
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }
}
```

---

## Documentation References

- **Full API Documentation:** `docs/MOBILE_API_DOCUMENTATION.md`
- **Profile Integration Guide:** `docs/MOBILE_APP_PROFILE_INTEGRATION.md`
- **Water Level Display:** `docs/MOBILE_APP_WATER_LEVEL_DISPLAY.md`

---

## Support

If you encounter any issues:
1. Check the endpoint in the API documentation
2. Test the endpoint with Postman/Insomnia
3. Verify authentication token is valid
4. Check network connectivity
5. Review error response format

**All backend endpoints are production-ready and fully tested!** 🚀
