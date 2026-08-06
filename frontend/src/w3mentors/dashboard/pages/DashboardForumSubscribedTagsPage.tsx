import { useCallback, useEffect, useRef, useState } from 'react';
import { api, type Paginated } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { ForumSpriteIcon } from '../components/ForumSpriteIcon';

type SubscribedTag = {
  id: number;
  name: string;
  is_deleted: boolean;
  is_inactive: boolean;
  show_alert: boolean;
};

type SystemTag = {
  id: number;
  name: string;
};

type SuggestTag = {
  id: number;
  name: string;
};

/** Legacy dashboard/views/forum/subscribed-tags-index.php + search.php + system-tags-list.php */
export function DashboardForumSubscribedTagsPage() {
  const { lbl } = useSite();
  const [subscribed, setSubscribed] = useState<SubscribedTag[]>([]);
  const [systemTags, setSystemTags] = useState<SystemTag[]>([]);
  const [systemMeta, setSystemMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [systemPage, setSystemPage] = useState(1);
  const [loadingSubscribed, setLoadingSubscribed] = useState(true);
  const [loadingSystem, setLoadingSystem] = useState(true);
  const [keyword, setKeyword] = useState('');
  const [suggestions, setSuggestions] = useState<SuggestTag[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [subscribing, setSubscribing] = useState(false);
  const suggestTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const searchWrapRef = useRef<HTMLDivElement>(null);

  const loadSubscribed = useCallback(() => {
    setLoadingSubscribed(true);
    return api
      .get<{ data: SubscribedTag[] }>('/dashboard/forum-subscribed-tags')
      .then((res) => setSubscribed(res.data.data))
      .catch(() => setSubscribed([]))
      .finally(() => setLoadingSubscribed(false));
  }, []);

  const loadSystemTags = useCallback(() => {
    setLoadingSystem(true);
    return api
      .get<{ data: SystemTag[]; meta: Paginated<unknown>['meta'] }>('/dashboard/forum-tags', {
        params: { page: systemPage, per_page: 50 },
      })
      .then((res) => {
        setSystemTags(res.data.data);
        setSystemMeta(res.data.meta);
      })
      .catch(() => {
        setSystemTags([]);
        setSystemMeta(null);
      })
      .finally(() => setLoadingSystem(false));
  }, [systemPage]);

  useEffect(() => {
    loadSubscribed();
  }, [loadSubscribed]);

  useEffect(() => {
    loadSystemTags();
  }, [loadSystemTags]);

  useEffect(() => {
    const onDocClick = (e: MouseEvent) => {
      if (searchWrapRef.current && !searchWrapRef.current.contains(e.target as Node)) {
        setShowSuggestions(false);
      }
    };
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, []);

  const fetchSuggestions = (value: string) => {
    if (suggestTimer.current) {
      clearTimeout(suggestTimer.current);
    }
    const trimmed = value.trim();
    if (!trimmed) {
      setSuggestions([]);
      setShowSuggestions(false);
      return;
    }
    suggestTimer.current = setTimeout(() => {
      api
        .get<{ data: SuggestTag[] }>('/dashboard/forum-tags/suggest', { params: { keyword: trimmed } })
        .then((res) => {
          setSuggestions(res.data.data);
          setShowSuggestions(true);
        })
        .catch(() => {
          setSuggestions([]);
          setShowSuggestions(false);
        });
    }, 500);
  };

  const subscribeTag = async (tagId: number, label?: string) => {
    if (tagId < 1 || subscribing) {
      return;
    }
    setSubscribing(true);
    try {
      await api.post('/dashboard/forum-subscribed-tags', { ftag_id: tagId });
      setKeyword(label ?? '');
      setShowSuggestions(false);
      await loadSubscribed();
    } catch {
      window.alert(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
    } finally {
      setSubscribing(false);
    }
  };

  const unsubscribeTag = async (tagId: number) => {
    if (!window.confirm(lbl('LBL_CONFIRM_UNSUBSCRIBE_TAG', 'Are you sure you want to unsubscribe from this tag?'))) {
      return;
    }
    try {
      await api.delete(`/dashboard/forum-subscribed-tags/${tagId}`);
      await loadSubscribed();
    } catch {
      window.alert(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
    }
  };

  const unsubscribeAll = async () => {
    if (
      !window.confirm(
        lbl('LBL_CONFIRM_UNSUBSCRIBE_ALL_TAGS', 'Are you sure you want to unsubscribe from all tags?')
      )
    ) {
      return;
    }
    try {
      await api.delete('/dashboard/forum-subscribed-tags');
      await loadSubscribed();
    } catch {
      window.alert(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
    }
  };

  const alertMessage = lbl(
    'LBL_TAG_INACTIVE_OR_DELETED_SO_NO_FURTHER_NOTIFICATION_WILL_RECEIVE',
    'This tag is inactive or deleted, so you will not receive further notifications.'
  );

  return (
    <div className="container container--small">
      <div className="page__head">
        <div className="row align-items-center justify-content-between">
          <div className="col-sm-12">
            <h1>{lbl('LBL_SUBSCRIBED_TAGS', 'Subscribed tags')}</h1>
            <p className="m-0">
              {lbl(
                'LBL_USE_THIS_PAGE_TO_MANAGE_SUBSCRIPTION_FOR_FORUM_TAGS_AND_THEIR_CURRENT_STATUS_ETC',
                'Manage your subscriptions for forum tags and stay updated on new forum activities.'
              )}
            </p>
          </div>
        </div>
      </div>
      <div className="page__body">
        <div className="page-content">
          <div className="page-panel">
            <div className="page-panel__body">
              <div className="add-tag-form pb-4">
                <form onSubmit={(e) => e.preventDefault()}>
                  <div className="field-icon" ref={searchWrapRef}>
                    <ForumSpriteIcon id="request-tag" className="icon icon--request-tag color-black" />
                    <input
                      type="text"
                      className="form-control"
                      name="keyword"
                      autoComplete="off"
                      placeholder={lbl('LBL_FORUM_SEARCH_TAG_TO_SUBSCRIBE', 'Search a tag to subscribe')}
                      value={keyword}
                      onChange={(e) => {
                        setKeyword(e.target.value);
                        fetchSuggestions(e.target.value);
                      }}
                      onFocus={() => suggestions.length > 0 && setShowSuggestions(true)}
                    />
                    {showSuggestions && (
                      <ul className="dropdown-menu show" style={{ display: 'block', width: '100%' }}>
                        {suggestions.length === 0 ? (
                          <li className="dropdown-item-text color-secondary">
                            {lbl('LBL_FORUM_NO_TAG_FOUND_WRT_YOUR_SEARCH_KEYWORD', 'No tag found for your search.')}
                          </li>
                        ) : (
                          suggestions.map((tag) => (
                            <li key={tag.id}>
                              <button
                                type="button"
                                className="dropdown-item"
                                onClick={() => subscribeTag(tag.id, tag.name)}
                              >
                                {tag.name}
                              </button>
                            </li>
                          ))
                        )}
                      </ul>
                    )}
                  </div>
                </form>
                {subscribed.length > 0 && (
                  <div className="unsubscribe-all mt-3">
                    <button
                      type="button"
                      className="btn btn--bordered color-secondary"
                      onClick={unsubscribeAll}
                    >
                      {lbl('LBL_UNSUBSCRIBE_ALL', 'Unsubscribe all')}
                    </button>
                  </div>
                )}
              </div>

              <div className="tag-group pb-4">
                <h5>{lbl('LBL_SUBSCRIBED_TAGS_LIST', 'Subscribed tags list')}</h5>
                <div className="mt-2" id="listing">
                  {loadingSubscribed ? (
                    <p className="color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>
                  ) : subscribed.length === 0 ? (
                    <DashboardNoRecord />
                  ) : (
                    <div className="tags">
                      <div className="tags__overflow">
                        {subscribed.map((tag) => (
                          <a
                            key={tag.id}
                            id={`subscribedtag_${tag.id}`}
                            href="#"
                            className="tags__item badge badge--curve color-primary"
                            onClick={(e) => e.preventDefault()}
                          >
                            {tag.name}
                            <span
                              className="ms-3"
                              role="button"
                              tabIndex={0}
                              onClick={(e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                unsubscribeTag(tag.id);
                              }}
                              onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                  e.preventDefault();
                                  unsubscribeTag(tag.id);
                                }
                              }}
                            >
                              <ForumSpriteIcon id="cancel" className="icon icon--cancel icon--small" />
                            </span>
                            {tag.show_alert && (
                              <>
                                <ForumSpriteIcon
                                  id="alert"
                                  className="icon icon--alert icon--small is-click-js"
                                  onClick={(e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    window.alert(alertMessage);
                                  }}
                                />
                                <small style={{ display: 'none' }} className="note">
                                  {alertMessage}
                                </small>
                              </>
                            )}
                          </a>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>

              <div className="tag-group">
                <h5>{lbl('LBL_QUESTIONS_TAGS_LIST', 'Forum tags list')}</h5>
                <div className="mt-2" id="system-tags">
                  {loadingSystem ? (
                    <p className="color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>
                  ) : systemTags.length === 0 ? (
                    <DashboardNoRecord />
                  ) : (
                    <>
                      <div className="tags">
                        <div className="tags__overflow">
                          {systemTags.map((tag) => (
                            <a
                              key={tag.id}
                              data-tag-id={tag.id}
                              id={`systemtag_${tag.id}`}
                              href="#"
                              className="tags__item badge badge--curve color-primary"
                              onClick={(e) => {
                                e.preventDefault();
                                subscribeTag(tag.id);
                              }}
                            >
                              {tag.name}
                            </a>
                          ))}
                        </div>
                      </div>
                      {systemMeta && (
                        <DashboardListingPagination
                          meta={systemMeta}
                          page={systemPage}
                          loading={loadingSystem}
                          onPageChange={setSystemPage}
                        />
                      )}
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
