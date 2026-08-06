const API_URL = import.meta.env.VITE_API_URL || '/api/v1';

/** Legacy Afile::TYPE_USER_PROFILE_IMAGE */
const TYPE_USER_PROFILE_IMAGE = 4;

export function userProfileImageUrl(userId: number): string {
  return `${API_URL}/image/show/${TYPE_USER_PROFILE_IMAGE}/${userId}`;
}

export function legacyImageUrl(fileType: number, recordId: number, size: number | string = 0, langId = 0): string {
  return `${API_URL}/image/show/${fileType}/${recordId}/${size}${langId > 0 ? `/${langId}` : ''}`;
}

export function legacyFileUrl(fileId: number): string {
  return `${API_URL}/image/show-by-id/${fileId}`;
}

export function legacyFlagUrl(countryCode: string): string {
  return `${API_URL}/flags/${countryCode.toLowerCase()}`;
}
