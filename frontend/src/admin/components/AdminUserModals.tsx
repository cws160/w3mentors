import { useCallback, useEffect, useState, type FormEvent, type ReactNode } from 'react';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi, type PaginatedMeta } from '../api/adminClient';
import { AdminLegacyPagination } from './AdminLegacyPagination';
import { AdminModal } from './AdminModal';

const sanitizePhoneNumber = (value: string) => value.replace(/[^0-9()+\-\s]/g, '').slice(0, 16);
const ADMIN_USER_TYPE_TEACHER = 2;
const ADMIN_PASSWORD_REGEX = /^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%_-]{8,15}$/;

type UserView = {
  id: number;
  full_name: string;
  email: string;
  username: string;
  timezone: string;
  created_at: string;
  last_seen_at?: string;
  phone_display: string;
  country_name: string;
  biography: string;
  verified?: boolean;
  active?: boolean;
  featured?: boolean;
  is_teacher?: boolean;
  is_affiliate?: boolean;
  registered_as?: number | null;
};

type CountryOption = {
  id: number;
  name: string;
  dial_code: string;
  phone_label: string;
};

type TimezoneOption = {
  id: string;
  label: string;
};

type UserTypeOption = {
  id: number;
  label_key: string;
};

type UserEdit = {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  username: string;
  email_username: string;
  country_id: number;
  phone_code: number;
  phone_number: string;
  featured: boolean;
  is_teacher: boolean;
  user_type: number;
  timezone: string;
  timezone_locked: boolean;
  is_parent_account: boolean;
};

type TransactionRow = {
  id: number;
  txn_id_formatted: string;
  amount_formatted: string;
  comment: string;
  created_at: string;
};

type AddressRow = {
  id: number;
  formatted: string;
  is_default: boolean;
};

export type AdminUserModalType =
  | 'view'
  | 'edit'
  | 'create'
  | 'transactions'
  | 'transaction-form'
  | 'addresses'
  | 'change-password';

type Props = {
  active: { type: AdminUserModalType; userId: number } | null;
  canEdit: boolean;
  onClose: () => void;
  onOpen: (type: AdminUserModalType, userId: number) => void;
  onUpdated: () => void;
};

function LegacyFormField({
  label,
  children,
  required,
  after,
}: {
  label: string;
  children: ReactNode;
  required?: boolean;
  after?: ReactNode;
}) {
  return (
    <div className="col-md-12">
      <div className="field-set">
        <div className="caption-wraper">
          <label className="field_label">
            {label}
            {required ? <span className="spn_must_field">*</span> : null}
          </label>
        </div>
        <div className="field-wraper">
          {children}
          {after}
        </div>
      </div>
    </div>
  );
}

export function AdminUserModals({ active, canEdit, onClose, onOpen, onUpdated }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [createFormLoading, setCreateFormLoading] = useState(false);
  const [createSaving, setCreateSaving] = useState(false);
  const [editFormLoading, setEditFormLoading] = useState(false);
  const [editSaving, setEditSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const [viewData, setViewData] = useState<UserView | null>(null);
  const [editUser, setEditUser] = useState<UserEdit | null>(null);
  const [countries, setCountries] = useState<CountryOption[]>([]);
  const [transactions, setTransactions] = useState<TransactionRow[]>([]);
  const [txnMeta, setTxnMeta] = useState<PaginatedMeta>({ current_page: 1, per_page: 10, total: 0, last_page: 1 });
  const [txnPage, setTxnPage] = useState(1);
  const [addresses, setAddresses] = useState<AddressRow[]>([]);

  const [txnType, setTxnType] = useState('1');
  const [txnAmount, setTxnAmount] = useState('');
  const [txnDescription, setTxnDescription] = useState('');
  const [txnErrors, setTxnErrors] = useState<{ type?: string; amount?: string; description?: string }>({});
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passwordErrors, setPasswordErrors] = useState<{ new_password?: string; conf_new_password?: string }>({});
  const [createUserType, setCreateUserType] = useState('');
  const [createEmailUsername, setCreateEmailUsername] = useState('');
  const [createUsername, setCreateUsername] = useState('');
  const [createFirstName, setCreateFirstName] = useState('');
  const [createLastName, setCreateLastName] = useState('');
  const [createPhoneCode, setCreatePhoneCode] = useState(0);
  const [createPhoneNumber, setCreatePhoneNumber] = useState('');
  const [createCountryId, setCreateCountryId] = useState(0);
  const [createTimezone, setCreateTimezone] = useState('');
  const [createFeatured, setCreateFeatured] = useState('');
  const [userTypes, setUserTypes] = useState<UserTypeOption[]>([]);
  const [timezones, setTimezones] = useState<TimezoneOption[]>([]);

  const userId = active?.userId ?? 0;
  const modalType = active?.type ?? null;

  const resetMessages = () => {
    setError('');
    setSuccess('');
    setPasswordErrors({});
    setTxnErrors({});
  };

  const loadView = useCallback(async (id: number) => {
    setLoading(true);
    resetMessages();
    try {
      const res = await adminApi.userView(id);
      setViewData(res.data.data as UserView);
    } catch (e: unknown) {
      setError((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Failed to load user');
    } finally {
      setLoading(false);
    }
  }, []);

  const loadEdit = useCallback(async (id: number) => {
    setEditFormLoading(true);
    resetMessages();
    try {
      const res = await adminApi.userEditForm(id);
      setEditUser(res.data.user as UserEdit);
      setCountries(res.data.countries ?? []);
      setUserTypes(res.data.user_types ?? []);
      setTimezones(res.data.timezones ?? []);
    } catch (e: unknown) {
      setError((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Failed to load form');
    } finally {
      setEditFormLoading(false);
    }
  }, []);

  const loadTransactions = useCallback(async (id: number, page: number) => {
    setLoading(true);
    resetMessages();
    try {
      const res = await adminApi.userTransactions(id, page);
      setTransactions(res.data.data as TransactionRow[]);
      setTxnMeta(res.data.meta);
    } catch (e: unknown) {
      setError((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Failed to load transactions');
    } finally {
      setLoading(false);
    }
  }, []);

  const loadCreate = useCallback(async () => {
    setCreateFormLoading(true);
    resetMessages();
    try {
      const res = await adminApi.userCreateForm();
      setCountries(res.data.countries ?? []);
      setUserTypes(res.data.user_types ?? []);
      setTimezones(res.data.timezones ?? []);
      setCreateCountryId(res.data.default_country_id ?? 0);
    } catch (e: unknown) {
      setError((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Failed to load form');
    } finally {
      setCreateFormLoading(false);
    }
  }, []);

  const loadAddresses = useCallback(async (id: number) => {
    setLoading(true);
    resetMessages();
    try {
      const res = await adminApi.userAddresses(id);
      setAddresses(res.data.data as AddressRow[]);
    } catch (e: unknown) {
      setError((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Failed to load addresses');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!active) return;
    if (active.type === 'view') void loadView(active.userId);
    if (active.type === 'edit') void loadEdit(active.userId);
    if (active.type === 'transactions') {
      setTxnPage(1);
    }
    if (active.type === 'transaction-form') {
      setTxnType('1');
      setTxnAmount('');
      setTxnDescription('');
      resetMessages();
    }
    if (active.type === 'addresses') void loadAddresses(active.userId);
    if (active.type === 'change-password') {
      setNewPassword('');
      setConfirmPassword('');
      resetMessages();
    }
    if (active.type === 'create') {
      setCreateUserType('');
      setCreateEmailUsername('');
      setCreateUsername('');
      setCreateFirstName('');
      setCreateLastName('');
      setCreatePhoneCode(0);
      setCreatePhoneNumber('');
      setCreateCountryId(0);
      setCreateTimezone('');
      setCreateFeatured('');
      resetMessages();
      void loadCreate();
    }
  }, [active, loadAddresses, loadCreate, loadEdit, loadTransactions, loadView]);

  useEffect(() => {
    if (modalType === 'transactions' && userId > 0) {
      void loadTransactions(userId, txnPage);
    }
  }, [txnPage, modalType, userId, loadTransactions]);

  const formatDate = (value: string) => {
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM DD, YYYY hh:mm A') : value;
  };

  const formatTimezone = (timezone: string) => {
    if (!timezone) return lbl('LBL_NA', 'N/A');
    const name = lbl(`TMZ_${timezone}`, timezone);
    const template = lbl('LBL_UTC_{offset}_{name}', 'UTC {offset} {name}');
    return template.replace('{offset}', '').replace('{name}', name).replace(/\s+/g, ' ').trim();
  };

  const formatUserType = (user: UserView) => {
    if (user.is_teacher || user.registered_as === ADMIN_USER_TYPE_TEACHER) return lbl('LBL_TEACHER', 'Teacher');
    if (user.is_affiliate || user.registered_as === 5) return lbl('LBL_AFFILIATE', 'Affiliate');
    return lbl('LBL_LEARNER', 'Learner');
  };

  const formatYesNo = (value?: boolean) => (value ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No'));

  const submitEdit = async (e: FormEvent) => {
    e.preventDefault();
    if (!editUser) return;
    setEditSaving(true);
    resetMessages();
    try {
      await adminApi.updateUser(editUser.id, {
        user_type: editUser.user_type,
        first_name: editUser.first_name,
        last_name: editUser.last_name,
        phone_code: editUser.phone_code,
        phone_number: editUser.phone_number,
        country_id: editUser.country_id,
        featured: editUser.featured,
        is_parent_account: editUser.is_parent_account,
      });
      setSuccess(lbl('LBL_USER_UPDATED_SUCCESSFULLY', 'User updated successfully'));
      onUpdated();
      setTimeout(onClose, 600);
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Update failed');
    } finally {
      setEditSaving(false);
    }
  };

  const submitTransaction = async (e: FormEvent) => {
    e.preventDefault();
    resetMessages();
    const nextErrors: { type?: string; amount?: string; description?: string } = {};
    if (!txnType) {
      nextErrors.type = lbl('MSG_SELECT_TYPE', 'Please select type');
    }
    if (!txnAmount || Number(txnAmount) <= 0) {
      nextErrors.amount = lbl('MSG_AMOUNT_IS_MANDATORY', 'Amount is mandatory');
    }
    if (!txnDescription.trim()) {
      nextErrors.description = lbl('MSG_DESCRIPTION_IS_MANDATORY', 'Description is mandatory');
    }
    if (Object.keys(nextErrors).length > 0) {
      setTxnErrors(nextErrors);
      return;
    }

    setLoading(true);
    try {
      await adminApi.createUserTransaction(userId, {
        type: Number(txnType),
        amount: Number(txnAmount),
        description: txnDescription,
      });
      onOpen('transactions', userId);
      onUpdated();
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Transaction failed');
    } finally {
      setLoading(false);
    }
  };

  const submitCreate = async (e: FormEvent) => {
    e.preventDefault();
    setCreateSaving(true);
    resetMessages();
    try {
      await adminApi.createUser({
        user_type: Number(createUserType),
        email_username: createEmailUsername,
        username: Number(createUserType) === ADMIN_USER_TYPE_TEACHER ? createUsername : '',
        first_name: createFirstName,
        last_name: createLastName,
        phone_code: createPhoneCode,
        phone_number: createPhoneNumber,
        country_id: createCountryId,
        timezone: createTimezone,
        featured: Number(createUserType) === ADMIN_USER_TYPE_TEACHER && createFeatured === '1',
      });
      setSuccess(lbl('LBL_USER_CREATED_SUCCESSFULLY', 'User created successfully'));
      onUpdated();
      setTimeout(onClose, 600);
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Create failed');
    } finally {
      setCreateSaving(false);
    }
  };

  const submitPassword = async (e: FormEvent) => {
    e.preventDefault();
    resetMessages();
    const nextErrors: { new_password?: string; conf_new_password?: string } = {};
    if (!newPassword || !ADMIN_PASSWORD_REGEX.test(newPassword)) {
      nextErrors.new_password = lbl(
        'MSG_ENTER_8_DIGIT_ALPHANUMERIC_PASSWORD',
        'Please enter a 8 digit alphanumeric password',
      );
    }
    if (!confirmPassword) {
      nextErrors.conf_new_password = lbl('MSG_CONFIRM_NEW_PASSWORD_MANDATORY', 'Confirm new password Is mandatory');
    } else if (newPassword !== confirmPassword) {
      nextErrors.conf_new_password = lbl('MSG_CONFIRM_PASSWORD_MUST_MATCH', 'Confirm password must match');
    }
    if (Object.keys(nextErrors).length > 0) {
      setPasswordErrors(nextErrors);
      return;
    }

    setLoading(true);
    try {
      await adminApi.changeUserPassword(userId, {
        new_password: newPassword,
        conf_new_password: confirmPassword,
      });
      setSuccess(lbl('LBL_PASSWORD_UPDATED_SUCCESSFULLY', 'Password updated successfully'));
      setTimeout(onClose, 600);
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_PASSWORD_UPDATE_FAILED', 'Password update failed');
      if (/confirm|match/i.test(message)) {
        setPasswordErrors({ conf_new_password: message });
      } else if (/password/i.test(message)) {
        setPasswordErrors({ new_password: message });
      } else {
        setError(message);
      }
    } finally {
      setLoading(false);
    }
  };

  const modalTitle = () => {
    switch (modalType) {
      case 'view':
        return lbl('LBL_VIEW_USER_DETAIL', 'View user detail');
      case 'edit':
        return lbl('LBL_USER_SETUP', 'User setup');
      case 'create':
        return lbl('LBL_USER_SETUP', 'User setup');
      case 'transactions':
        return lbl('LBL_USER_TRANSACTIONS', 'User transactions');
      case 'transaction-form':
        return lbl('LBL_ADD_USER_TRANSACTIONS', 'Add user transaction');
      case 'addresses':
        return lbl('LBL_USER_ADDRESSES', 'User addresses');
      case 'change-password':
        return lbl('LBL_CHANGE_PASSWORD', 'Change password');
      default:
        return '';
    }
  };

  const modalSize =
    modalType === 'edit' || modalType === 'create' || modalType === 'transaction-form'
      ? 'sm'
      : 'md';

  return (
    <AdminModal open={!!active} title={modalTitle()} size={modalSize} onClose={onClose}>
      {loading && modalType !== 'create' && modalType !== 'edit' ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : null}
      {error ? <div className="alert alert-danger m-3">{error}</div> : null}
      {success ? <div className="alert alert-success m-3">{success}</div> : null}

      {modalType === 'view' && viewData ? (
        <div className="form-edit-body p-0">
          <table className="table table-coloum">
            <tbody>
              <tr>
                <th>{lbl('LBL_NAME', 'Name')}</th>
                <td>{viewData.full_name}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_EMAIL', 'Email')}</th>
                <td>{viewData.email}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_USERNAME', 'Username')}</th>
                <td>{viewData.username || lbl('LBL_NA', 'N/A')}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_USER_TYPE', 'User Type')}</th>
                <td>{formatUserType(viewData)}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_TIMEZONE', 'Timezone')}</th>
                <td>{formatTimezone(viewData.timezone)}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_REG_DATE', 'Reg. date')}</th>
                <td>{formatDate(viewData.created_at)}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_PHONE_NO', 'Phone')}</th>
                <td dir="ltr">{viewData.phone_display || lbl('LBL_NA', 'N/A')}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_COUNTRY', 'Country')}</th>
                <td>{viewData.country_name || lbl('LBL_NA', 'N/A')}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_STATUS', 'Status')}</th>
                <td>{viewData.active ? lbl('LBL_ACTIVE', 'Active') : lbl('LBL_INACTIVE', 'Inactive')}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_EMAIL_VERIFIED', 'Email Verified')}</th>
                <td>{formatYesNo(viewData.verified)}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_FEATURED', 'Featured')}</th>
                <td>{formatYesNo(viewData.featured)}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_LAST_SEEN', 'Last seen')}</th>
                <td>{viewData.last_seen_at ? formatDate(viewData.last_seen_at) : lbl('LBL_NA', 'N/A')}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_BIOGRAPHY', 'Biography')}</th>
                <td style={{ whiteSpace: 'pre-wrap' }}>{viewData.biography || lbl('LBL_NA', 'N/A')}</td>
              </tr>
            </tbody>
          </table>
        </div>
      ) : null}

      {modalType === 'edit' ? (
        editFormLoading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : editUser ? (
        <div className="form-edit-body">
          <form id="frmUser" className="form form_horizontal" onSubmit={submitEdit}>
            <div className="row">
              <LegacyFormField label={lbl('LBL_SELECT_USER_TYPE', 'Select User Type')} required>
                <select
                  className="form-control"
                  name="user_type"
                  value={editUser.user_type || ''}
                  onChange={(e) => {
                    const userType = Number(e.target.value);
                    setEditUser({
                      ...editUser,
                      user_type: userType,
                      featured: userType === ADMIN_USER_TYPE_TEACHER ? editUser.featured : false,
                    });
                  }}
                  required
                >
                  <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                  {userTypes.map((type) => (
                    <option key={type.id} value={type.id}>
                      {lbl(
                        type.label_key,
                        type.id === 1 ? 'Learner' : type.id === 2 ? 'Teacher' : 'Affiliate',
                      )}
                    </option>
                  ))}
                </select>
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_EMAIL/USERNAME', 'Email/Username')} required>
                <input
                  className="form-control"
                  name="user_email"
                  value={editUser.email_username}
                  readOnly
                  disabled
                />
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_FIRST_NAME', 'First name')} required>
                <input
                  className="form-control"
                  name="user_first_name"
                  value={editUser.first_name}
                  onChange={(e) => setEditUser({ ...editUser, first_name: e.target.value })}
                  required
                />
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_LAST_NAME', 'Last name')} required>
                <input
                  className="form-control"
                  name="user_last_name"
                  value={editUser.last_name}
                  onChange={(e) => setEditUser({ ...editUser, last_name: e.target.value })}
                  required
                />
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_PHONE_CODE', 'Phone code')} required>
                <select
                  id="user_phone_code"
                  className="form-control"
                  name="user_phone_code"
                  value={editUser.phone_code || ''}
                  onChange={(e) => setEditUser({ ...editUser, phone_code: Number(e.target.value) })}
                  required
                >
                  <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                  {countries.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.phone_label}
                    </option>
                  ))}
                </select>
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_PHONE', 'Phone')} required>
                <input
                  id="user_phone"
                  className="form-control"
                  type="tel"
                  inputMode="tel"
                  pattern="[0-9()+\-\s]{4,16}"
                  maxLength={16}
                  title={lbl('LBL_ENTER_VALID_PHONE_NUMBER', 'Please enter a valid phone number.')}
                  name="user_phone_number"
                  dir="ltr"
                  value={editUser.phone_number}
                  onChange={(e) => setEditUser({ ...editUser, phone_number: sanitizePhoneNumber(e.target.value) })}
                  required
                />
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_COUNTRY', 'Country')} required>
                <select
                  id="user_country_id"
                  className="form-control"
                  name="user_country_id"
                  value={editUser.country_id || ''}
                  onChange={(e) => setEditUser({ ...editUser, country_id: Number(e.target.value) })}
                  required
                >
                  <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                  {countries.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
                </select>
              </LegacyFormField>
              <LegacyFormField
                label={lbl('LBL_TIMEZONE', 'Timezone')}
                required
                after={
                  <small className="color-secondary">
                    {lbl(
                      'htmlAfterField_admin_user_timezone',
                      'You will not be able to edit the timezone once set as users can update it from their profile settings.',
                    )}
                  </small>
                }
              >
                <select
                  id="user_timezone"
                  className="form-control"
                  name="user_timezone"
                  value={editUser.timezone}
                  onChange={(e) => setEditUser({ ...editUser, timezone: e.target.value })}
                  required
                  disabled={editUser.timezone_locked}
                >
                  <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                  {timezones.map((tz) => (
                    <option key={tz.id} value={tz.id}>
                      {tz.label}
                    </option>
                  ))}
                </select>
              </LegacyFormField>
              {editUser.user_type === ADMIN_USER_TYPE_TEACHER ? (
                <LegacyFormField label={lbl('LBL_FEATURED', 'Featured')} required>
                  <select
                    className="form-control"
                    name="user_featured"
                    value={editUser.featured ? '1' : '0'}
                    onChange={(e) => setEditUser({ ...editUser, featured: e.target.value === '1' })}
                    required
                  >
                    <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                    <option value="1">{lbl('LBL_YES', 'Yes')}</option>
                    <option value="0">{lbl('LBL_NO', 'No')}</option>
                  </select>
                </LegacyFormField>
              ) : null}
              <div className="col-md-12">
                <div className="field-set">
                  <div className="field-wraper">
                    <button
                      type="submit"
                      name="btn_submit"
                      className="btn btn-brand"
                      disabled={editSaving}
                    >
                      {lbl('LBL_SAVE_CHANGES', 'Save changes')}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        ) : null
      ) : null}

      {modalType === 'transactions' ? (
        <>
          <div className="form-edit-head">
            <nav className="tab tab-inline">
              <ul className="tabs-nav">
                <li>
                  <a className="active" href="javascript:void(0)">
                    {lbl('LBL_TRANSACTIONS', 'Transactions')}
                  </a>
                </li>
                {canEdit ? (
                  <li>
                    <a href="javascript:void(0)" onClick={() => onOpen('transaction-form', userId)}>
                      {lbl('LBL_ADD_NEW', 'Add new')}
                    </a>
                  </li>
                ) : null}
              </ul>
            </nav>
          </div>
          <div className="form-edit-body ps-0 pe-0">
            <table className="table fixed-layout" width="100%">
              <thead>
                <tr>
                  <th>{lbl('LBL_TRANSACTION_ID', 'Transaction ID')}</th>
                  <th>{lbl('LBL_USERTXN_DATE_TIME', 'Date/time')}</th>
                  <th>{lbl('LBL_USERTXN_AMOUNT', 'Amount')}</th>
                  <th>{lbl('LBL_DESCRIPTION', 'Description')}</th>
                </tr>
              </thead>
              <tbody>
                {transactions.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="text-center">
                      {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                    </td>
                  </tr>
                ) : (
                  transactions.map((row) => (
                    <tr key={row.id}>
                      <td>{row.txn_id_formatted}</td>
                      <td>{formatDate(row.created_at)}</td>
                      <td>{row.amount_formatted}</td>
                      <td>{row.comment}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
            <AdminLegacyPagination
              page={txnPage}
              lastPage={txnMeta.last_page}
              perPage={txnMeta.per_page}
              total={txnMeta.total}
              onPageChange={setTxnPage}
              labels={{
                showing: lbl('LBL_Showing', 'Showing'),
                to: lbl('LBL_to', 'to'),
                of: lbl('LBL_of', 'of'),
                entries: lbl('LBL_Entries', 'Entries'),
              }}
            />
          </div>
        </>
      ) : null}

      {modalType === 'transaction-form' ? (
        <>
          <div className="form-edit-head">
            <nav className="tab tab-inline">
              <ul className="tabs-nav">
                <li>
                  <a href="javascript:void(0)" onClick={() => onOpen('transactions', userId)}>
                    {lbl('LBL_TRANSACTIONS', 'Transactions')}
                  </a>
                </li>
                <li>
                  <a className="active" href="javascript:void(0)">
                    {lbl('LBL_ADD_NEW', 'Add new')}
                  </a>
                </li>
              </ul>
            </nav>
          </div>
          <div className="form-edit-body">
            <form id="addressFrm" className="form" onSubmit={submitTransaction} noValidate>
              <div className="form-group">
                <label>{lbl('LBL_TYPE', 'Type')}</label>
                <select
                  className={`form-control${txnErrors.type ? ' error' : ''}`}
                  value={txnType}
                  onChange={(e) => {
                    setTxnType(e.target.value);
                    setTxnErrors((prev) => ({ ...prev, type: undefined }));
                  }}
                  required
                >
                  <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                  <option value="1">{lbl('LBL_Credit', 'Credit')}</option>
                  <option value="2">{lbl('LBL_Debit', 'Debit')}</option>
                </select>
                {txnErrors.type ? <div className="field-error">{txnErrors.type}</div> : null}
              </div>
              <div className="form-group">
                <label>{lbl('LBL_USERTXN_AMOUNT', 'Amount')}</label>
                <input
                  className={`form-control${txnErrors.amount ? ' error' : ''}`}
                  type="number"
                  min={1}
                  step="0.01"
                  value={txnAmount}
                  onChange={(e) => {
                    setTxnAmount(e.target.value);
                    setTxnErrors((prev) => ({ ...prev, amount: undefined }));
                  }}
                  required
                />
                {txnErrors.amount ? <div className="field-error">{txnErrors.amount}</div> : null}
              </div>
              <div className="form-group">
                <label>{lbl('LBL_DESCRIPTION', 'Description')}</label>
                <textarea
                  className={`form-control${txnErrors.description ? ' error' : ''}`}
                  value={txnDescription}
                  onChange={(e) => {
                    setTxnDescription(e.target.value);
                    setTxnErrors((prev) => ({ ...prev, description: undefined }));
                  }}
                  required
                />
                {txnErrors.description ? <div className="field-error">{txnErrors.description}</div> : null}
              </div>
              <button type="submit" name="btn_submit" className="btn btn-brand" disabled={loading}>
                {lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </form>
          </div>
        </>
      ) : null}

      {modalType === 'addresses' ? (
        <div className="form-edit-body p-0">
          <table className="table table-coloum">
            <tbody>
              <tr>
                <th>{lbl('LBL_Sr_No.', 'Sr. No.')}</th>
                <th>{lbl('LBL_Addresses', 'Addresses')}</th>
              </tr>
              {addresses.length === 0 ? (
                <tr>
                  <td colSpan={2}>{lbl('LBL_NO_RECORDS_FOUND', 'No records found')}</td>
                </tr>
              ) : (
                addresses.map((row, index) => (
                  <tr key={row.id} className={row.is_default ? 'table-secondary' : undefined}>
                    <td>{index + 1}</td>
                    <td>
                      {row.formatted}
                      {row.is_default ? (
                        <span className="link-primary"> ({lbl('LBL_DEFAULT_ADDRESS', 'Default address')})</span>
                      ) : null}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      ) : null}

      {modalType === 'change-password' ? (
        <div className="form-edit-body">
          <form className="form form_horizontal" onSubmit={submitPassword} noValidate>
            <div className="form-group">
              <label htmlFor="new_password">{lbl('LBL_NEW_PASSWORD', 'New password')}</label>
              <input
                id="new_password"
                className={`form-control${passwordErrors.new_password ? ' error' : ''}`}
                type="password"
                value={newPassword}
                onChange={(e) => {
                  setNewPassword(e.target.value);
                  setPasswordErrors((prev) => ({ ...prev, new_password: undefined }));
                }}
                required
              />
              {passwordErrors.new_password ? <div className="field-error">{passwordErrors.new_password}</div> : null}
            </div>
            <div className="form-group">
              <label htmlFor="conf_new_password">{lbl('LBL_CONFIRM_NEW_PASSWORD', 'Confirm new password')}</label>
              <input
                id="conf_new_password"
                className={`form-control${passwordErrors.conf_new_password ? ' error' : ''}`}
                type="password"
                value={confirmPassword}
                onChange={(e) => {
                  setConfirmPassword(e.target.value);
                  setPasswordErrors((prev) => ({ ...prev, conf_new_password: undefined }));
                }}
                required
              />
              {passwordErrors.conf_new_password ? (
                <div className="field-error">{passwordErrors.conf_new_password}</div>
              ) : null}
            </div>
            <button type="submit" name="btn_submit" id="btn_submit" className="btn btn-brand" disabled={loading}>
              {lbl('LBL_SAVE_CHANGES', 'Save changes')}
            </button>
          </form>
        </div>
      ) : null}

      {modalType === 'create' ? (
        <div className="form-edit-body">
          <form id="frmAddUser" className="form form_horizontal" onSubmit={submitCreate}>
            <div className="row">
              <LegacyFormField label={lbl('LBL_SELECT_USER_TYPE', 'Select User Type')} required>
                <select
                  className="form-control"
                  name="user_type"
                  value={createUserType}
                  onChange={(e) => {
                    setCreateUserType(e.target.value);
                    if (Number(e.target.value) !== ADMIN_USER_TYPE_TEACHER) {
                      setCreateFeatured('');
                      setCreateUsername('');
                    }
                  }}
                  required
                  disabled={createFormLoading}
                >
                  <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                  {userTypes.map((type) => (
                    <option key={type.id} value={type.id}>
                      {lbl(
                        type.label_key,
                        type.id === 1 ? 'Learner' : type.id === 2 ? 'Teacher' : 'Affiliate',
                      )}
                    </option>
                  ))}
                </select>
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_EMAIL/USERNAME', 'Email/Username')} required>
                <input
                  className="form-control"
                  name="user_email"
                  autoComplete="off"
                  value={createEmailUsername}
                  onChange={(e) => setCreateEmailUsername(e.target.value)}
                  required
                />
              </LegacyFormField>
              {Number(createUserType) === ADMIN_USER_TYPE_TEACHER ? (
                <LegacyFormField label={lbl('LBL_USERNAME', 'Username')} required>
                  <input
                    className="form-control"
                    name="user_username"
                    autoComplete="off"
                    value={createUsername}
                    onChange={(e) => setCreateUsername(e.target.value)}
                    required
                  />
                </LegacyFormField>
              ) : null}
              <LegacyFormField label={lbl('LBL_FIRST_NAME', 'First name')} required>
                <input
                  className="form-control"
                  name="user_first_name"
                  value={createFirstName}
                  onChange={(e) => setCreateFirstName(e.target.value)}
                  required
                />
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_LAST_NAME', 'Last name')}>
                <input
                  className="form-control"
                  name="user_last_name"
                  value={createLastName}
                  onChange={(e) => setCreateLastName(e.target.value)}
                />
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_PHONE_CODE', 'Phone code')} required>
                <select
                  id="user_phone_code"
                  className="form-control"
                  name="user_phone_code"
                  value={createPhoneCode || ''}
                  onChange={(e) => setCreatePhoneCode(Number(e.target.value))}
                  required
                  disabled={createFormLoading}
                >
                  <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                  {countries.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.phone_label}
                    </option>
                  ))}
                </select>
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_PHONE', 'Phone')} required>
                <input
                  id="user_phone"
                  className="form-control"
                  type="tel"
                  inputMode="tel"
                  pattern="[0-9()+\-\s]{4,16}"
                  maxLength={16}
                  title={lbl('LBL_ENTER_VALID_PHONE_NUMBER', 'Please enter a valid phone number.')}
                  name="user_phone_number"
                  dir="ltr"
                  value={createPhoneNumber}
                  onChange={(e) => setCreatePhoneNumber(sanitizePhoneNumber(e.target.value))}
                  required
                />
              </LegacyFormField>
              <LegacyFormField label={lbl('LBL_COUNTRY', 'Country')} required>
                <select
                  id="user_country_id"
                  className="form-control"
                  name="user_country_id"
                  value={createCountryId || ''}
                  onChange={(e) => setCreateCountryId(Number(e.target.value))}
                  required
                  disabled={createFormLoading}
                >
                  <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                  {countries.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
                </select>
              </LegacyFormField>
              <LegacyFormField
                label={lbl('LBL_TIMEZONE', 'Timezone')}
                required
                after={
                  <small className="color-secondary">
                    {lbl(
                      'htmlAfterField_admin_user_timezone',
                      'You will not be able to edit the timezone once set as users can update it from their profile settings.',
                    )}
                  </small>
                }
              >
                <select
                  id="user_timezone"
                  className="form-control"
                  name="user_timezone"
                  value={createTimezone}
                  onChange={(e) => setCreateTimezone(e.target.value)}
                  required
                  disabled={createFormLoading}
                >
                  <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                  {timezones.map((tz) => (
                    <option key={tz.id} value={tz.id}>
                      {tz.label}
                    </option>
                  ))}
                </select>
              </LegacyFormField>
              {Number(createUserType) === ADMIN_USER_TYPE_TEACHER ? (
                <LegacyFormField label={lbl('LBL_FEATURED', 'Featured')} required>
                  <select
                    className="form-control"
                    name="user_featured"
                    value={createFeatured}
                    onChange={(e) => setCreateFeatured(e.target.value)}
                    required
                    disabled={createFormLoading}
                  >
                    <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                    <option value="1">{lbl('LBL_YES', 'Yes')}</option>
                    <option value="0">{lbl('LBL_NO', 'No')}</option>
                  </select>
                </LegacyFormField>
              ) : null}
              <div className="col-md-12">
                <div className="field-set">
                  <div className="field-wraper">
                    <button
                      type="submit"
                      name="btn_submit"
                      className="btn btn-brand"
                      disabled={createSaving || createFormLoading}
                    >
                      {lbl('LBL_SAVE_CHANGES', 'Save changes')}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      ) : null}
    </AdminModal>
  );
}

export async function openUserLogin(userId: number): Promise<void> {
  const res = await adminApi.userLogin(userId);
  const { token, redirect_url: redirect } = res.data;
  const url = `/admin/user-login?token=${encodeURIComponent(token)}&redirect=${encodeURIComponent(redirect)}`;
  window.open(url, '_blank', 'noopener,noreferrer');
}
