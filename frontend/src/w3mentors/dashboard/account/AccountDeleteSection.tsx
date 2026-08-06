import { useSite } from '../../context/SiteContext';
import { useModal } from '../../context/ModalContext';
import { api } from '../../../api/client';
import { AccountDeleteModal } from './AccountDeleteModal';

/** Legacy: dashboard/views/account/delete-account.php (injected into #formBlock-js). */
export function AccountDeleteSection() {
  const { lbl } = useSite();
  const { showModal, closeModal } = useModal();

  const deleteAccount = async () => {
    if (
      !window.confirm(
        lbl(
          'LBL_GDPR_DELETE_ACCOUNT_REQUEST_DESCRIPTION',
          'This request will delete all your personal data from the platform.'
        )
      )
    ) {
      return;
    }

    try {
      const res = await api.get<{ data: { has_pending_request: boolean } }>(
        '/users/me/delete-account'
      );
      if (res.data.data.has_pending_request) {
        window.alert(
          lbl(
            'LBL_REQUEST_IS_ALREADY_PLACED_TO_DELETE_ACCOUNT',
            'A request to delete this account is already pending.'
          )
        );
        return;
      }
    } catch {
      /* open modal anyway; submit will validate */
    }

    showModal(
      <AccountDeleteModal onClose={closeModal} onSubmitted={closeModal} />,
      { size: 'modal-md' }
    );
  };

  return (
    <div className="content-panel">
      <div className="content-panel__head border-bottom mb-4">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h5>{lbl('LBL_DELETE_ACCOUNT', 'Delete account')}</h5>
          </div>
          <div />
        </div>
      </div>
      <div className="content-panel__body">
        <div className="form">
          <div className="form__body">
            <div className="account-deactivation-info">
              <h6 className="mb-2">{lbl('LBL_DELETE_ACCOUNT_CONFIRMATION', '')}</h6>
              <p>{lbl('LBL_DELETE_ACCOUNT_DESCRIPTION', '')}</p>
              <div className="pt-4">
                <a
                  href="javascript:void(0)"
                  className="btn btn--primary"
                  onClick={(e) => {
                    e.preventDefault();
                    deleteAccount();
                  }}
                >
                  {lbl('LBL_DELETE_MY_ACCOUNT', 'Delete my account')}
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
