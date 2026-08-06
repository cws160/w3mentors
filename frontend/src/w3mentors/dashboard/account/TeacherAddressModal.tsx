import { useEffect, useRef, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { useGoogleMapsScript } from './useGoogleMapsScript';
import type {
  TeacherAddressForm,
  TeacherAddressMeta,
  TeacherAddressRecord,
} from './teacherAddressTypes';

type Props = {
  meta: TeacherAddressMeta;
  editing: TeacherAddressRecord | null;
  initialForm: TeacherAddressForm;
  onClose: () => void;
  onSaved: (meta: TeacherAddressMeta) => void;
};

export function TeacherAddressModal({ meta, editing, initialForm, onClose, onSaved }: Props) {
  const { lbl, langId } = useSite();
  const [form, setForm] = useState<TeacherAddressForm>(initialForm);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const mapReady = useGoogleMapsScript(meta.google_maps_key || undefined);
  const mapRef = useRef<HTMLDivElement>(null);
  const autocompleteRef = useRef<HTMLInputElement>(null);
  const mapInstance = useRef<{
    setCenter: (c: { lat: number; lng: number }) => void;
    setZoom: (z: number) => void;
  } | null>(null);
  const markerRef = useRef<{
    setPosition: (c: { lat: number; lng: number }) => void;
    addListener: (ev: string, fn: () => void) => void;
    getPosition: () => { lat: () => number; lng: () => number } | null;
  } | null>(null);
  const mapInitialized = useRef(false);

  useEffect(() => {
    if (!mapReady || !mapRef.current || !window.google?.maps || mapInitialized.current) return;

    const lat = parseFloat(initialForm.latitude) || 40.7259931;
    const lng = parseFloat(initialForm.longitude) || -74.0019471;
    const center = { lat, lng };

    const g = window.google.maps as {
      Map: new (el: HTMLElement, opts: object) => {
        setCenter: (c: { lat: number; lng: number }) => void;
        setZoom: (z: number) => void;
      };
      Marker: new (opts: object) => {
        setPosition: (c: { lat: number; lng: number }) => void;
        addListener: (ev: string, fn: () => void) => void;
        getPosition: () => { lat: () => number; lng: () => number } | null;
      };
      places?: {
        Autocomplete: new (input: HTMLInputElement, opts: object) => {
          addListener: (ev: string, fn: () => void) => void;
          getPlace: () => {
            geometry?: { location: { lat: () => number; lng: () => number } };
            formatted_address?: string;
            name?: string;
            place_id?: string;
            address_components?: { long_name: string; types: string[] }[];
          };
        };
      };
    };

    mapInstance.current = new g.Map(mapRef.current, {
      center,
      zoom: initialForm.address ? 16 : 4,
      mapTypeControl: true,
    });
    markerRef.current = new g.Marker({
      map: mapInstance.current,
      position: center,
      draggable: true,
    });
    markerRef.current.addListener('dragend', () => {
      const pos = markerRef.current?.getPosition();
      if (!pos) return;
      setForm((f) => ({
        ...f,
        latitude: String(pos.lat()),
        longitude: String(pos.lng()),
      }));
    });

    if (autocompleteRef.current && g.places) {
      const autocomplete = new g.places.Autocomplete(autocompleteRef.current, {
        fields: ['formatted_address', 'geometry', 'name', 'place_id', 'address_components'],
      });
      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (!place.geometry?.location || !mapInstance.current || !markerRef.current) return;
        const plat = place.geometry.location.lat();
        const plng = place.geometry.location.lng();
        let street = '';
        let city = '';
        let zip = '';
        for (const comp of place.address_components ?? []) {
          const types = comp.types ?? [];
          if (types.includes('street_number')) {
            street = `${comp.long_name} ${street}`.trim();
          }
          if (types.includes('route')) {
            street = `${street} ${comp.long_name}`.trim();
          }
          if (types.includes('locality')) {
            city = comp.long_name;
          }
          if (types.includes('postal_code')) {
            zip = comp.long_name;
          }
        }
        if (autocompleteRef.current && place.formatted_address) {
          autocompleteRef.current.value = place.formatted_address;
        }
        setForm((f) => ({
          ...f,
          address: street || place.formatted_address || f.address,
          city: city || f.city,
          zipcode: zip || f.zipcode,
          place_id: place.place_id ?? '',
          place_name: place.name ?? '',
          latitude: String(plat),
          longitude: String(plng),
        }));
        mapInstance.current.setCenter({ lat: plat, lng: plng });
        mapInstance.current.setZoom(16);
        markerRef.current.setPosition({ lat: plat, lng: plng });
      });
    }

    mapInitialized.current = true;
  }, [mapReady, initialForm.address, initialForm.latitude, initialForm.longitude]);

  useEffect(() => {
    if (!mapInstance.current || !markerRef.current) return;
    const lat = parseFloat(form.latitude);
    const lng = parseFloat(form.longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
    mapInstance.current.setCenter({ lat, lng });
    markerRef.current.setPosition({ lat, lng });
  }, [form.latitude, form.longitude]);

  const save = async () => {
    setSaving(true);
    setError('');
    const payload = {
      phone: form.phone,
      address: form.address,
      city: form.city,
      state_id: form.state_id,
      zipcode: form.zipcode,
      type: form.type,
      latitude: parseFloat(form.latitude),
      longitude: parseFloat(form.longitude),
      place_id: form.place_id,
      place_name: form.place_name,
      is_default: form.is_default,
    };
    try {
      const url = editing
        ? `/account/teacher/addresses/${editing.id}`
        : '/account/teacher/addresses';
      const res = editing
        ? await api.put<{ data: TeacherAddressMeta }>(url, payload, { params: { lang_id: langId } })
        : await api.post<{ data: TeacherAddressMeta }>(url, payload, { params: { lang_id: langId } });
      onSaved(res.data.data);
      onClose();
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  const useCurrentLocation = () => {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition((pos) => {
      setForm((f) => ({
        ...f,
        latitude: String(pos.coords.latitude),
        longitude: String(pos.coords.longitude),
      }));
    });
  };

  return (
    <>
      <div className="modal-header">
        <h5>{lbl('LBL_Address', 'Address')}</h5>
        <button type="button" className="btn-close w3mentorsmodalJs" aria-label="" onClick={onClose} />
      </div>
      <div className="modal-body">
        <div className="row">
          <div className="col-md-6 order-md-2">
            <div className="field-set">
              <div className="caption-wraper">
                <label className="field_label">
                  {lbl('LBL_ENTER_A_LOCATION', 'Enter a location')}
                  <span className="spn_must_field">*</span>
                </label>
              </div>
              <div className="field-wraper">
                <div className="field_cover">
                  {meta.google_maps_key ? (
                    <input
                      ref={autocompleteRef}
                      id="google-autocomplete"
                      type="text"
                      className="form-control"
                      defaultValue={
                        editing
                          ? [form.address, form.city, form.zipcode].filter(Boolean).join(', ')
                          : ''
                      }
                      placeholder={lbl('LBL_ENTER_A_LOCATION', 'Enter a location')}
                      autoComplete="off"
                    />
                  ) : (
                    <p className="text-muted small">
                      {lbl(
                        'LBL_GOOGLE_MAP_NOT_CONFIGURED',
                        'Map search is unavailable. Use “Use current location” or enter coordinates below.'
                      )}
                    </p>
                  )}
                </div>
              </div>
            </div>
            <div id="map" ref={mapRef} className="mb-4" style={{ aspectRatio: '1 / 1', minHeight: 200 }} />
            {!meta.google_maps_key && (
              <button type="button" className="btn btn--bordered btn--small mb-3" onClick={useCurrentLocation}>
                {lbl('LBL_USE_CURRENT_LOCATION', 'Use current location')}
              </button>
            )}
          </div>
          <div className="col-md-6">
            <form
              className="form"
              onSubmit={(e) => {
                e.preventDefault();
                save();
              }}
            >
              <div className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_Street', 'Street')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <input
                          className="form-control"
                          value={form.address}
                          onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))}
                          required
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_Phone', 'Phone')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <input
                          className="form-control"
                          value={form.phone}
                          onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                          required
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_City', 'City')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <input
                          className="form-control"
                          value={form.city}
                          onChange={(e) => setForm((f) => ({ ...f, city: e.target.value }))}
                          required
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_State', 'State')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <select
                          className="form-control"
                          value={form.state_id || ''}
                          onChange={(e) =>
                            setForm((f) => ({ ...f, state_id: Number(e.target.value) }))
                          }
                          required
                        >
                          <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                          {meta.states.map((s) => (
                            <option key={s.id} value={s.id}>
                              {s.name}
                            </option>
                          ))}
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_Type', 'Type')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <select
                          className="form-control"
                          value={form.type}
                          onChange={(e) => setForm((f) => ({ ...f, type: Number(e.target.value) }))}
                          required
                        >
                          {meta.address_types.map((t) => (
                            <option key={t.id} value={t.id}>
                              {t.label}
                            </option>
                          ))}
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_Zipcode', 'Zipcode')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <input
                          className="form-control"
                          value={form.zipcode}
                          onChange={(e) => setForm((f) => ({ ...f, zipcode: e.target.value }))}
                          required
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        <span className="checkbox">
                          <input
                            type="checkbox"
                            checked={form.is_default}
                            onChange={(e) =>
                              setForm((f) => ({ ...f, is_default: e.target.checked }))
                            }
                          />
                          <i className="input-helper" />
                        </span>
                        {lbl('LBL_Default', 'Default')}
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              {!meta.google_maps_key && (
                <div className="row">
                  <div className="col-md-6">
                    <input
                      className="form-control"
                      placeholder="Latitude"
                      value={form.latitude}
                      onChange={(e) => setForm((f) => ({ ...f, latitude: e.target.value }))}
                    />
                  </div>
                  <div className="col-md-6">
                    <input
                      className="form-control"
                      placeholder="Longitude"
                      value={form.longitude}
                      onChange={(e) => setForm((f) => ({ ...f, longitude: e.target.value }))}
                    />
                  </div>
                </div>
              )}
              {error && <p className="color-primary">{error}</p>}
              <div className="row">
                <div className="col-sm-12">
                  <div className="field-set">
                    <div className="field-wraper form-buttons-group">
                      <button type="submit" className="btn btn--primary" disabled={saving}>
                        {saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_SAVE', 'Save')}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </>
  );
}
