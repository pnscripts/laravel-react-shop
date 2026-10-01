// Alias "react" to this file in your plugin build so it shares the storefront's React.
const React = window.PnShop.React;

export default React;
export const {
    Children,
    Component,
    Fragment,
    PureComponent,
    StrictMode,
    Suspense,
    cloneElement,
    createContext,
    createElement,
    forwardRef,
    isValidElement,
    lazy,
    memo,
    startTransition,
    use,
    useCallback,
    useContext,
    useDeferredValue,
    useEffect,
    useId,
    useLayoutEffect,
    useMemo,
    useReducer,
    useRef,
    useState,
    useSyncExternalStore,
    useTransition,
} = React;
