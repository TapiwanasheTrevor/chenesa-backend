# Mobile App - Profile Features Integration Guide

## Overview

This document outlines all the backend endpoints available for implementing the missing profile features in the mobile app. All endpoints are now fully implemented and ready for integration.

**Base URL:** `https://chenesa-shy-grass-3201.fly.dev/api`

---

## Implementation Status Summary

| Feature | Backend Status | Mobile UI Status | Integration Required |
|---------|---------------|------------------|---------------------|
| Edit Profile | ✅ Complete | ✅ Complete | ✅ Working |
| Profile Avatar | ✅ Complete | ⚠️ Placeholder | 🔧 Needs Integration |
| Change Password | ✅ Complete | ✅ Complete | ✅ Working |
| Notification Preferences | ✅ Complete | ✅ Complete | 🔧 Needs Integration |
| Security Settings | ✅ Complete | ⚠️ Partial | 🔧 Needs Integration |
| Logout All Devices | ✅ Complete | ⚠️ Partial | 🔧 Needs Integration |
| Help & Support | ❌ Not Implemented | ❌ Shows "Coming Soon" | ⏳ Future Enhancement |

---

## 1. Notification Preferences (READY FOR INTEGRATION)

### Current Issue
Mobile app has UI ready but uses simulated API calls (`Future.delayed`) instead of real backend integration.

### Backend Endpoints

#### GET /api/notifications/preferences

**Mobile App Format (Flat Structure):**
```http
GET /api/notifications/preferences?format=flat
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "push_notifications": true,
    "email_notifications": false,
    "sms_notifications": false,
    "tank_alerts": true,
    "maintenance_reminders": true,
    "system_updates": true,
    "low_level_alerts": true,
    "critical_level_alerts": true,
    "sensor_offline_alerts": true,
    "weekly_reports": false,
    "monthly_reports": false
  }
}
```

#### PATCH /api/notifications/preferences

**Request (Mobile App Format):**
```json
{
  "push_notifications": true,
  "email_notifications": true,
  "sms_notifications": false,
  "tank_alerts": true,
  "maintenance_reminders": true,
  "system_updates": false,
  "low_level_alerts": true,
  "critical_level_alerts": true,
  "sensor_offline_alerts": true,
  "weekly_reports": true,
  "monthly_reports": false
}
```

**Response:**
```json
{
  "message": "Notification preferences updated successfully",
  "data": {
    "push_notifications": true,
    "email_notifications": true,
    // ... all other fields
  }
}
```

### Mobile App Integration (Flutter)

**Replace in `lib/screens/profile/notifications_screen.dart`:**

```dart
// BEFORE (lines 35-36):
// TODO: Implement backend API call for notification settings
// final success = await authProvider.updateNotificationSettings(settings);

// AFTER:
final apiService = ApiService();

// Load preferences on screen init
@override
void initState() {
  super.initState();
  _loadPreferences();
}

Future<void> _loadPreferences() async {
  try {
    final prefs = await apiService.getNotificationPreferences();
    setState(() {
      _settings = prefs;
    });
  } catch (e) {
    // Handle error
  }
}

// Save preferences (replace line 64)
Future<void> _saveSettings() async {
  setState(() => _isLoading = true);

  try {
    await apiService.updateNotificationPreferences(_settings);

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Notification settings saved successfully'),
          backgroundColor: Colors.green,
        ),
      );
    }
  } catch (e) {
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to save settings: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  } finally {
    if (mounted) {
      setState(() => _isLoading = false);
    }
  }
}
```

**Add to `lib/services/api_service.dart`:**

```dart
// Get notification preferences (mobile app format)
Future<Map<String, dynamic>> getNotificationPreferences() async {
  final response = await _dio.get(
    '/notifications/preferences',
    queryParameters: {'format': 'flat'},
  );
  return response.data['data'];
}

// Update notification preferences
Future<void> updateNotificationPreferences(Map<String, dynamic> preferences) async {
  await _dio.patch(
    '/notifications/preferences',
    data: preferences,
  );
}
```

---

## 2. Security Settings (READY FOR INTEGRATION)

### Current Issue
Security screen only has change password working. Other settings show "coming soon" messages.

### Backend Endpoints

#### GET /api/profile/security-settings

```http
GET /api/profile/security-settings
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "two_factor_enabled": false,
    "session_timeout_enabled": true,
    "session_timeout_minutes": 30,
    "biometric_enabled": false
  }
}
```

#### PATCH /api/profile/security-settings

**Request:**
```json
{
  "two_factor_enabled": false,
  "session_timeout_enabled": true,
  "session_timeout_minutes": 30,
  "biometric_enabled": false
}
```

**Response:**
```json
{
  "message": "Security settings updated successfully",
  "data": {
    "two_factor_enabled": false,
    "session_timeout_enabled": true,
    "session_timeout_minutes": 30,
    "biometric_enabled": false
  }
}
```

### Mobile App Integration

**Replace in `lib/screens/profile/security_screen.dart` (line 48):**

```dart
// BEFORE:
// TODO: Implement backend API call for security settings
await Future.delayed(const Duration(seconds: 1));

// AFTER:
final apiService = ApiService();

// Load settings on init
@override
void initState() {
  super.initState();
  _loadSecuritySettings();
}

Future<void> _loadSecuritySettings() async {
  try {
    final settings = await apiService.getSecuritySettings();
    setState(() {
      _twoFactorEnabled = settings['two_factor_enabled'] ?? false;
      _sessionTimeout = settings['session_timeout_enabled'] ?? true;
      _sessionTimeoutMinutes = settings['session_timeout_minutes'] ?? 30;
    });
  } catch (e) {
    // Handle error
  }
}

// Save settings
Future<void> _saveSecuritySettings() async {
  try {
    await apiService.updateSecuritySettings({
      'two_factor_enabled': _twoFactorEnabled,
      'session_timeout_enabled': _sessionTimeout,
      'session_timeout_minutes': _sessionTimeoutMinutes,
      'biometric_enabled': false, // Local only
    });

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Security settings saved')),
      );
    }
  } catch (e) {
    // Handle error
  }
}
```

**Add to `lib/services/api_service.dart`:**

```dart
Future<Map<String, dynamic>> getSecuritySettings() async {
  final response = await _dio.get('/profile/security-settings');
  return response.data['data'];
}

Future<void> updateSecuritySettings(Map<String, dynamic> settings) async {
  await _dio.patch('/profile/security-settings', data: settings);
}
```

---

## 3. Profile Avatar Upload (READY FOR INTEGRATION)

### Current Issue
Edit profile screen has placeholder for avatar upload but doesn't implement it.

### Backend Endpoints

#### POST /api/profile/avatar

**Request:**
```http
POST /api/profile/avatar
Authorization: Bearer {token}
Content-Type: multipart/form-data

avatar: [binary image file]
```

**Validation:**
- Allowed formats: JPEG, PNG, JPG, GIF
- Max size: 2MB
- Must be a valid image file

**Response:**
```json
{
  "message": "Avatar uploaded successfully",
  "avatar_url": "https://chenesa-shy-grass-3201.fly.dev/storage/avatars/abc123.jpg",
  "updated_at": "2025-01-08T10:30:00.000000Z"
}
```

#### DELETE /api/profile/avatar

```http
DELETE /api/profile/avatar
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Avatar deleted successfully",
  "updated_at": "2025-01-08T10:30:00.000000Z"
}
```

### Mobile App Integration

**Replace in `lib/screens/profile/edit_profile_screen.dart` (line 156):**

```dart
// BEFORE:
// TODO: Implement profile picture upload

// AFTER:
import 'package:image_picker/image_picker.dart';

Future<void> _pickAndUploadAvatar() async {
  final ImagePicker picker = ImagePicker();

  // Show options: Camera or Gallery
  final source = await showModalBottomSheet<ImageSource>(
    context: context,
    builder: (context) => SafeArea(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: const Icon(Icons.photo_camera),
            title: const Text('Take Photo'),
            onTap: () => Navigator.pop(context, ImageSource.camera),
          ),
          ListTile(
            leading: const Icon(Icons.photo_library),
            title: const Text('Choose from Gallery'),
            onTap: () => Navigator.pop(context, ImageSource.gallery),
          ),
        ],
      ),
    ),
  );

  if (source == null) return;

  // Pick image
  final XFile? image = await picker.pickImage(
    source: source,
    maxWidth: 800,
    maxHeight: 800,
    imageQuality: 85,
  );

  if (image == null) return;

  // Upload
  setState(() => _isLoading = true);

  try {
    final apiService = ApiService();
    final avatarUrl = await apiService.uploadAvatar(File(image.path));

    setState(() {
      _avatarUrl = avatarUrl;
    });

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profile picture updated')),
      );
    }
  } catch (e) {
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to upload: $e')),
      );
    }
  } finally {
    setState(() => _isLoading = false);
  }
}

Future<void> _deleteAvatar() async {
  setState(() => _isLoading = true);

  try {
    final apiService = ApiService();
    await apiService.deleteAvatar();

    setState(() {
      _avatarUrl = null;
    });

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profile picture removed')),
      );
    }
  } catch (e) {
    // Handle error
  } finally {
    setState(() => _isLoading = false);
  }
}
```

**Add to `lib/services/api_service.dart`:**

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

  final response = await _dio.post(
    '/profile/avatar',
    data: formData,
  );

  return response.data['avatar_url'];
}

Future<void> deleteAvatar() async {
  await _dio.delete('/profile/avatar');
}
```

**Add dependency to `pubspec.yaml`:**
```yaml
dependencies:
  image_picker: ^1.0.4
```

---

## 4. Logout All Devices (READY FOR INTEGRATION)

### Current Issue
Security screen has "Logout All Devices" button but shows "coming soon" message.

### Backend Endpoint

#### POST /api/profile/logout-all

**Request:**
```json
{
  "password": "user_password"
}
```

**Response:**
```json
{
  "message": "Successfully logged out from all other devices",
  "devices_logged_out": 3
}
```

**Error Response (Wrong Password):**
```json
{
  "message": "Incorrect password",
  "errors": {
    "password": ["The password is incorrect"]
  }
}
```

### Mobile App Integration

**Replace in `lib/screens/profile/security_screen.dart`:**

```dart
Future<void> _showLogoutAllDialog() async {
  final passwordController = TextEditingController();

  final confirmed = await showDialog<bool>(
    context: context,
    builder: (context) => AlertDialog(
      title: const Text('Logout All Devices'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text(
            'This will log you out from all devices except this one. '
            'Please enter your password to confirm.',
          ),
          const SizedBox(height: 16),
          TextField(
            controller: passwordController,
            decoration: const InputDecoration(
              labelText: 'Password',
              border: OutlineInputBorder(),
            ),
            obscureText: true,
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context, false),
          child: const Text('Cancel'),
        ),
        ElevatedButton(
          onPressed: () => Navigator.pop(context, true),
          child: const Text('Logout All'),
        ),
      ],
    ),
  );

  if (confirmed != true) return;

  try {
    final apiService = ApiService();
    final result = await apiService.logoutAllDevices(passwordController.text);

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Logged out from ${result['devices_logged_out']} devices',
          ),
          backgroundColor: Colors.green,
        ),
      );
    }
  } catch (e) {
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }
}
```

**Add to `lib/services/api_service.dart`:**

```dart
Future<Map<String, dynamic>> logoutAllDevices(String password) async {
  final response = await _dio.post(
    '/profile/logout-all',
    data: {'password': password},
  );
  return response.data;
}
```

---

## 5. Help & Support (NOT IMPLEMENTED)

### Current Status
❌ Backend endpoints not implemented
❌ Mobile UI shows "coming soon"

### Recommended Future Implementation

**Suggested Endpoints:**
```
GET  /api/help/articles          - List help articles
GET  /api/help/articles/{id}     - Get article details
POST /api/support/tickets        - Create support ticket
GET  /api/support/tickets        - List user's tickets
GET  /api/support/tickets/{id}   - Get ticket details
```

**Alternative: Simple Implementation**
- Link to external help documentation (web page)
- Email support link (`mailto:support@chenesa.io`)
- WhatsApp support link (if available)

---

## Complete API Service Reference

Here's a complete reference of all profile-related methods that should be in your `ApiService` class:

```dart
class ApiService {
  final Dio _dio;

  // === PROFILE ===

  Future<User> updateProfile({
    String? firstName,
    String? lastName,
    String? phone,
  }) async {
    final response = await _dio.patch('/profile', data: {
      if (firstName != null) 'first_name': firstName,
      if (lastName != null) 'last_name': lastName,
      if (phone != null) 'phone': phone,
    });
    return User.fromJson(response.data['user']);
  }

  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    await _dio.post('/profile/change-password', data: {
      'current_password': currentPassword,
      'new_password': newPassword,
      'new_password_confirmation': newPassword,
    });
  }

  // === AVATAR ===

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

  // === NOTIFICATIONS ===

  Future<Map<String, dynamic>> getNotificationPreferences() async {
    final response = await _dio.get(
      '/notifications/preferences',
      queryParameters: {'format': 'flat'},
    );
    return response.data['data'];
  }

  Future<void> updateNotificationPreferences(
    Map<String, dynamic> preferences
  ) async {
    await _dio.patch('/notifications/preferences', data: preferences);
  }

  // === SECURITY ===

  Future<Map<String, dynamic>> getSecuritySettings() async {
    final response = await _dio.get('/profile/security-settings');
    return response.data['data'];
  }

  Future<void> updateSecuritySettings(Map<String, dynamic> settings) async {
    await _dio.patch('/profile/security-settings', data: settings);
  }

  Future<Map<String, dynamic>> logoutAllDevices(String password) async {
    final response = await _dio.post(
      '/profile/logout-all',
      data: {'password': password},
    );
    return response.data;
  }
}
```

---

## Testing Checklist

### 1. Notification Preferences
- [ ] Load preferences on screen open
- [ ] Toggle switches update correctly
- [ ] Save button sends data to backend
- [ ] Success/error messages display
- [ ] Preferences persist after app restart

### 2. Security Settings
- [ ] Load security settings on screen open
- [ ] Session timeout toggle works
- [ ] Session timeout minutes updates
- [ ] Settings save to backend
- [ ] Change password still works

### 3. Profile Avatar
- [ ] Camera option works
- [ ] Gallery picker works
- [ ] Image upload succeeds
- [ ] Avatar displays in profile
- [ ] Delete avatar works
- [ ] Large images are compressed

### 4. Logout All Devices
- [ ] Password prompt appears
- [ ] Correct password logs out other devices
- [ ] Incorrect password shows error
- [ ] Success message displays device count

---

## Error Handling

All endpoints return standard error responses:

**Validation Errors (422):**
```json
{
  "message": "The given data was invalid",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

**Authentication Errors (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Server Errors (500):**
```json
{
  "message": "Failed to perform action",
  "error": "Detailed error message"
}
```

### Recommended Error Handling Pattern

```dart
try {
  await apiService.someMethod();
  // Success
} on DioException catch (e) {
  if (e.response?.statusCode == 422) {
    // Validation errors
    final errors = e.response?.data['errors'];
    // Show specific field errors
  } else if (e.response?.statusCode == 401) {
    // Unauthorized - redirect to login
  } else {
    // Generic error
    final message = e.response?.data['message'] ?? 'An error occurred';
    // Show error message
  }
} catch (e) {
  // Network or other errors
  showError('Network error. Please check your connection.');
}
```

---

## Summary of Changes Required

1. **notifications_screen.dart:**
   - Remove `Future.delayed` simulation
   - Add `_loadPreferences()` method
   - Update `_saveSettings()` to use API

2. **security_screen.dart:**
   - Add `_loadSecuritySettings()` method
   - Implement `_saveSecuritySettings()`
   - Implement `_showLogoutAllDialog()`

3. **edit_profile_screen.dart:**
   - Add image picker functionality
   - Implement `_pickAndUploadAvatar()`
   - Implement `_deleteAvatar()`

4. **api_service.dart:**
   - Add all new API methods listed above

5. **pubspec.yaml:**
   - Add `image_picker: ^1.0.4` dependency

---

## Next Steps

1. ✅ **Phase 1: Notification Preferences** (Easiest)
   - Update `notifications_screen.dart`
   - Add API methods to `api_service.dart`
   - Test toggles and save functionality

2. ✅ **Phase 2: Security Settings** (Medium)
   - Update `security_screen.dart`
   - Add API methods
   - Test settings persistence

3. ✅ **Phase 3: Avatar Upload** (Requires Image Picker)
   - Add image_picker dependency
   - Update `edit_profile_screen.dart`
   - Test camera and gallery
   - Test upload and delete

4. ✅ **Phase 4: Logout All Devices** (Simple)
   - Add password dialog
   - Implement logout all functionality
   - Test with multiple devices

All backend endpoints are **fully implemented and ready for integration**. The mobile app just needs to replace the placeholder/simulation code with real API calls.