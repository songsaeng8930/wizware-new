/**
 * BMS 공용 내보내기 유틸
 *   - CSV 다운로드(UTF-8 BOM 부착으로 엑셀 한글 호환)
 *   - 브라우저 인쇄(PDF) 전환
 *
 * 사용 예:
 *   BmsExport.table('#attTable', '근태_2026-04.csv');
 *   BmsExport.rows([['이름','금액'],['홍길동',1000]], 'test.csv');
 *   BmsExport.print();
 */
(function (global) {
  'use strict';

  var UTF8_BOM = '﻿';

  function sanitize(value) {
    if (value === null || value === undefined) return '';
    var s = String(value);
    // 내부 공백 압축(줄바꿈→공백), 양끝 공백 제거
    s = s.replace(/\r?\n/g, ' ').replace(/\s+/g, ' ').trim();
    // CSV 인젝션 방지: =, +, -, @ 로 시작하면 작은따옴표 프리픽스
    if (/^[=+\-@]/.test(s)) s = "'" + s;
    // 쌍따옴표 이스케이프 + 구분자/줄바꿈 포함 시 감싸기
    if (/[",\n]/.test(s)) s = '"' + s.replace(/"/g, '""') + '"';
    return s;
  }

  function rowsToCsv(rows) {
    return rows.map(function (r) { return r.map(sanitize).join(','); }).join('\r\n');
  }

  function download(filename, content, mime) {
    var blob = new Blob([UTF8_BOM + content], { type: (mime || 'text/csv') + ';charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename || ('export_' + Date.now() + '.csv');
    document.body.appendChild(a);
    a.click();
    setTimeout(function () {
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }, 0);
  }

  /**
   * 테이블 엘리먼트(지정 선택자)를 읽어 CSV 다운로드.
   * - thead > tr > th 는 헤더, tbody > tr > td 는 데이터
   * - 특정 컬럼 제외 시 `data-export-skip` 속성을 부여
   * - 테이블에 `data-export-file` 속성이 있으면 기본 파일명으로 사용
   */
  function exportTable(selector, filename, opts) {
    opts = opts || {};
    var tbl = (typeof selector === 'string') ? document.querySelector(selector) : selector;
    if (!tbl) {
      console.warn('[BmsExport] table not found:', selector);
      return false;
    }
    if (!filename) {
      filename = tbl.getAttribute('data-export-file') || ('export_' + Date.now() + '.csv');
    }

    var rows = [];
    var skip = {}; // 컬럼 index 스킵

    // 헤더
    var headCells = tbl.querySelectorAll('thead tr:last-child th');
    if (headCells.length) {
      var head = [];
      headCells.forEach(function (th, i) {
        if (th.hasAttribute('data-export-skip')) { skip[i] = true; return; }
        head.push(th.innerText || th.textContent);
      });
      rows.push(head);
    }

    // 데이터 행
    var dataRows = tbl.querySelectorAll('tbody tr');
    dataRows.forEach(function (tr) {
      if (tr.hasAttribute('data-export-skip')) return;
      if (tr.classList.contains('emp-empty')) return;
      var row = [];
      Array.prototype.forEach.call(tr.children, function (td, i) {
        if (skip[i]) return;
        if (td.hasAttribute('data-export-skip')) return;
        row.push(td.innerText || td.textContent);
      });
      if (row.length) rows.push(row);
    });

    if (rows.length <= 1) {
      alert('내보낼 데이터가 없습니다.');
      return false;
    }

    download(filename, rowsToCsv(rows));
    return true;
  }

  /** 배열(2차원)로 직접 CSV 생성 */
  function exportRows(rows, filename) {
    if (!Array.isArray(rows) || rows.length === 0) {
      alert('내보낼 데이터가 없습니다.');
      return false;
    }
    download(filename, rowsToCsv(rows));
    return true;
  }

  /** 브라우저 인쇄 대화창 열기 (PDF로 저장 가능) */
  function print() {
    window.print();
  }

  global.BmsExport = {
    table: exportTable,
    rows: exportRows,
    print: print
  };
})(window);
