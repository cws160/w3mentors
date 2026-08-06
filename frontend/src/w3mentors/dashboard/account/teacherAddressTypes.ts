export type TeacherAddressRecord = {
  id: number;
  phone: string;
  address: string;
  city: string;
  state_id: number;
  state_name: string;
  country_id: number;
  country_name: string;
  zipcode: string;
  place_id: string;
  place_name: string;
  latitude: number | null;
  longitude: number | null;
  type: number;
  type_label: string;
  is_default: boolean;
  formatted: string;
};

export type TeacherAddressMeta = {
  module_enabled: boolean;
  google_maps_key: string;
  country_id: number;
  address_types: { id: number; label: string }[];
  states: { id: number; name: string }[];
  addresses: TeacherAddressRecord[];
};

export type TeacherAddressForm = {
  phone: string;
  address: string;
  city: string;
  state_id: number;
  zipcode: string;
  type: number;
  latitude: string;
  longitude: string;
  place_id: string;
  place_name: string;
  is_default: boolean;
};

export const emptyAddressForm = (): TeacherAddressForm => ({
  phone: '',
  address: '',
  city: '',
  state_id: 0,
  zipcode: '',
  type: 1,
  latitude: '',
  longitude: '',
  place_id: '',
  place_name: '',
  is_default: false,
});

export function addressToForm(row: TeacherAddressRecord): TeacherAddressForm {
  return {
    phone: row.phone,
    address: row.address,
    city: row.city,
    state_id: row.state_id,
    zipcode: row.zipcode,
    type: row.type,
    latitude: row.latitude != null ? String(row.latitude) : '',
    longitude: row.longitude != null ? String(row.longitude) : '',
    place_id: row.place_id,
    place_name: row.place_name,
    is_default: row.is_default,
  };
}
