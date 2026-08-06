export function adminLegacyConfirms(lbl: (key: string, fallback?: string) => string) {
  const deleteMessage = lbl('LBL_DO_YOU_WANT_TO_DELETE', 'Do you want to delete?');

  return {
    delete: deleteMessage,
    remove: () => Promise.resolve(window.confirm(deleteMessage)),
    updateStatus: lbl(
      'LBL_ARE_YOU_SURE_YOU_WANT_TO_UPDATE_STATUS?',
      'Are you sure you want to update status?',
    ),
    deleteImage: lbl('LBL_DO_YOU_WANT_TO_DELETE_IMAGE', 'Do you want to delete image?'),
  };
}
